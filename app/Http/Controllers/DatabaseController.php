<?php

namespace App\Http\Controllers;

use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class DatabaseController extends Controller
{
    /**
     * Handle SELECT operations (GET requests)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @var DatabaseService
     */
    protected $databaseService;

    /**
     * DatabaseController constructor.
     * 
     * @param DatabaseService $databaseService
     */
    public function __construct(DatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    public function select(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table' => 'required|string',
                'columns' => 'nullable|array',
                'where' => 'nullable|array',
                'order_by' => 'nullable|array',
                'limit' => 'nullable|integer|min:1',
                'offset' => 'nullable|integer|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }
            
            $table = $request->input('table');
            $columns = $request->input('columns', ['*']);
            $where = $request->input('where', []);
            $orderBy = $request->input('order_by', []);
            $limit = $request->input('limit');
            $offset = $request->input('offset');
            
            // Get the authenticated user from the request
            $user = $request->auth_user;
            
            $results = $this->databaseService->select($table, $columns, $where, $orderBy, $limit, $offset, $user);
            
            return response()->json([
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid argument',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Process both SELECT and INSERT operations via POST request
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(Request $request)
    {
        $method = $request->input('method', 'insert');
        
        if ($method === 'select') {
            return $this->select($request);
        } else {
            return $this->insert($request);
        }
    }
    
    /**
     * Handle INSERT operations (POST requests)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function insert(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table' => 'required|string',
                'data' => 'required|array',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }
            
            $table = $request->input('table');
            $data = $request->input('data');
            
            // Get the authenticated user from the request
            $user = $request->auth_user;
            
            $result = $this->databaseService->insert($table, $data, $user);
            
            return response()->json([
                'success' => true,
                'inserted_id' => $result
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid argument',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle UPDATE operations (PUT requests)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table' => 'required|string',
                'data' => 'required|array',
                'where' => 'array',
                'upsert' => 'boolean',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }
            
            $table = $request->input('table');
            $data = $request->input('data');
            $where = $request->input('where', []);
            $upsert = $request->input('upsert', false);
            
            // Get the authenticated user from the request
            $user = $request->auth_user;
            
            $result = $this->databaseService->update($table, $data, $where, $upsert, $user);
            
            // Check if result is a newly inserted ID (from upsert)
            if ($upsert && is_numeric($result) && $result > 0 && !is_int($result)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Record inserted (upsert)',
                    'id' => $result
                ], 201);
            }
            
            // Result is the number of affected rows
            $affected = $result;
            
            return response()->json([
                'success' => true,
                'message' => $affected . ' record(s) updated',
                'affected' => $affected
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid argument',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle DELETE operations (DELETE requests)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'table' => 'required|string',
                'where' => 'required|array',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'messages' => $validator->errors()
                ], 422);
            }
            
            $table = $request->input('table');
            $where = $request->input('where', []);
            
            // Get the authenticated user from the request
            $user = $request->auth_user;
            
            $affected = $this->databaseService->delete($table, $where, $user);
            
            return response()->json([
                'success' => true,
                'message' => $affected . ' record(s) deleted',
                'affected' => $affected
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'error' => 'Invalid argument',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Validate SELECT request
     * 
     * @param Request $request
     * @return true|array True if valid, array of errors if invalid
     */
    private function validateSelectRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'columns' => 'array',
            'where' => 'array',
            'order_by' => 'array',
            'limit' => 'integer|min:1',
            'offset' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return true;
    }

    /**
     * Validate INSERT request
     * 
     * @param Request $request
     * @return true|array True if valid, array of errors if invalid
     */
    private function validateInsertRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return true;
    }

    /**
     * Validate UPDATE request
     * 
     * @param Request $request
     * @return true|array True if valid, array of errors if invalid
     */
    private function validateUpdateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'data' => 'required|array',
            'where' => 'array',
            'upsert' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return true;
    }

    /**
     * Validate DELETE request
     * 
     * @param Request $request
     * @return true|array True if valid, array of errors if invalid
     */
    private function validateDeleteRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table' => 'required|string',
            'where' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return true;
    }
}
