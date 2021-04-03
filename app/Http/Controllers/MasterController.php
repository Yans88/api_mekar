<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class MasterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    //

    public function index(Request $request)
    {
        $cms = (int)$request->cms > 0 ? (int)$request->cms : 0;
        $setting = DB::table('setting')->get()->toArray();
        $out = array();
        if (!empty($setting)) {
            foreach ($setting as $val) {
                $out[$val->setting_key] = $val->setting_val;
            }
        }
        if ($cms == 0) {
            unset($out['mail_pass']);
            unset($out['send_mail']);
            unset($out['content_reg']);
            unset($out['content_forgotPass']);
            unset($out['content_reg']);
            unset($out['content_forgotPin']);
            unset($out['about_us']);
            unset($out['subj_email_register']);
            unset($out['subj_email_forgot']);
            unset($out['term_condition']);
            unset($out['policy']);
        }
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $out
        );
        $id_member = (int)$request->id_member > 0 ? Helper::last_login((int)$request->id_member) : 0;
        return response($result);
    }

    function upd_setting(Request $request){
		$input  = $request->all();
		foreach($input as $key=>$val){
			$where = array();
			$dt = array();
			$where = array("setting_key"=>"$key");
			$dt = ["setting_val" => "$val"];
			DB::table('setting')->where($where)->update($dt);
		}
		$result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $input
        );
		return response($result);
	}
	
	public function get_kasus(Request $request)
    {
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'kasus';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
		if($sort_column == 'id_kasus ') $sort_column = "ABS('id_kasus ')";
		$sort_column .=' '.$sort_order;
        $where = ['deleted_at' => null];
        $count = 0;
        $_data = array();
        $result = array();
        if (!empty($keyword)) {
            $_data = DB::table('master_kasus')->select('master_kasus.*')
                                ->where($where)->whereRaw("LOWER(kasus) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('master_kasus')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('master_kasus')->select('master_kasus.id_kasus','kasus')                
                ->where($where)->offset($offset)->limit($per_page)->orderByRaw($sort_column)->get();
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ($count > 0) {            
            $result = array(
                'err_code'      => '00',
                'err_msg'          => 'ok',
                'total_data'    => $count,
                'data'          => $_data
            );
        }
        return response($result);
    }
	
	function add_kasus(Request $request){
		$result = array();
        $tgl = date('Y-m-d H:i:s');
		$data = array();
        $id = (int)$request->id_kasus > 0 ? (int)$request->id_kasus : 0;
		$data = array(            
            'kasus'   => $request->kasus
        );
		if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('master_kasus')->where('id_kasus', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator);
            $id = DB::table('master_kasus')->insertGetId($data, "id_kasus");
        }

        if ($id > 0) {
            $data += array('id_kasus' => $id);
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'insert has problem',
                'data'      => null
            );
        }
        return response($result);
	}
	
	function del_kasus(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id = (int)$request->id_kasus > 0 ? (int)$request->id_kasus : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('master_kasus')->where('id_kasus', $id)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }
	
	
}
