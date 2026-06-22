<?php

namespace App\Traits;

use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use PDOException;

trait HandlesTransaction
{
    public static function handleTransaction($callback){
        $data = '';
        $info = null;
        $status = false;

        try {
            $result = \DB::transaction($callback);
            $data = $result['data'];
            $info = $result['info'];
            $message = $result['message'];
            $status = isset($result['status']) ? $result['status'] : true;
        } catch (ValidationException $e) {
            // Re-throw so Inertia receives a proper 422 with form errors
            throw $e;
        } catch (QueryException $e) {
            $info = 'Transaction failed: ' . $e->getMessage();
            $message = 'Error occured';
        } catch (PDOException $e) {
            $info = 'Transaction failed due to a database driver error: ' . $e->getMessage();
            $message = 'Error occured';
        } catch (\Exception $e) {
            $info = null;
            $message = $e->getMessage();
        }

        return [
            'data' => ($data) ? $data : 'Nothing found.',
            'message' => $message,
            'info' => $info,
            'status' => $status,
        ];
    }
}
