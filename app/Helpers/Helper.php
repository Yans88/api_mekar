<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class Helper
{

    static function last_login($id_member = 0)
    {
        $tgl = date('Y-m-d H:i:s');
        DB::table('members')->where('id_member', $id_member)->update(['last_login' => $tgl]);
        return $id_member;
    }

    
}
