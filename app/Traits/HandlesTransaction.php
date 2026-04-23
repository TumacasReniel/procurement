<?php

namespace App\Traits;

use Illuminate\Database\QueryException;
use PDOException;

trait HandlesTransaction
{
    public static function handleTransaction($callback){
        $data = '';
        $info = null;
        $status = false;

        try {
            $result = \DB::transaction($callback);
            // $status = ($result['status'] === 'error') ? false : true;
            $data = $result['data'];
            $info = $result['info'];
            $message = $result['message'];
            // $status = $status;
            $status = isset($result['status']) ? $result['status'] : true;
        } catch (QueryException $e) {
            $info = 'Transaction failed: ' . $e->getMessage();
            $message = 'Error occured';
        } catch (PDOException $e) {
            $info = 'Transaction failed due to a database driver error: ' . $e->getMessage();
            $message = 'Error occured';
        } catch (\Exception $e) {
            $info = 'An unexpected error occurred: ' . $e->getMessage();
            $message = 'Error occured';
        }

        return [
            'data' => ($data) ? $data : 'Nothing found.',
            'message' => $message,
            'info' => $info,
            'status' => $status,
        ];
    }
}
