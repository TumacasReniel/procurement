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
        $message = null;
        $status = false;

        try {
            $result = \DB::transaction($callback);
            // Keys are optional: a missing one must not raise an "Undefined array key"
            // warning, which the error handler would turn into an exception and report
            // as a failure even though the transaction already committed.
            $data = data_get($result, 'data', '');
            $info = data_get($result, 'info');
            $message = data_get($result, 'message');
            $status = data_get($result, 'status') ?? true;
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
