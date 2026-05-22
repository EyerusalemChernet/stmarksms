<?php
echo 'DB check: ';
try {
    DB::connection()->getPdo();
    echo 'OK';
} catch (\Exception $e) {
    echo 'Fail: ' . $e->getMessage();
}
