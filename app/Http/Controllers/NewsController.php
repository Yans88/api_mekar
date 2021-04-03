<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
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
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'id_news';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
		if($sort_column == 'id_news') $sort_column = "ABS(id_news)";
		$sort_column .=' '.$sort_order;
        $where = ['deleted_at' => null];
        $count = 0;
        $_data = array();
        $data = array();
        if (!empty($keyword)) {
            $_data = DB::table('news')->select('news.*')                
                ->where($where)->whereRaw("LOWER(title) like '%" . $keyword . "%'")->get();
            $count = count($_data);
        } else {
            $count = DB::table('news')->where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $_data = DB::table('news')->select('news.*')                
                ->where($where)->offset($offset)->limit($per_page)->orderByRaw($sort_column)->get();
        }
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ($count > 0) {
            foreach ($_data as $d) {
                $path_img = null;
                $path_img  = !empty($d->img) ? env('APP_URL') . '/api_mekar/uploads/news/' . $d->img : null;
                unset($d->created_by);
                unset($d->updated_by);
                unset($d->deleted_by);
                unset($d->created_at);
                unset($d->updated_at);
                unset($d->deleted_at);
                unset($d->img);
                $d->img = $path_img;
                $data[] = $d;
            }
            $result = array(
                'err_code'      => '00',
                'err_msg'          => 'ok',
				'sort_column'	=> $sort_column,
                'total_data'    => $count,
                'data'          => $data
            );
        }
        return response($result);
    }

    function store(Request $request)
    {
        $result = array();
        $tgl = date('Y-m-d H:i:s');
        $_tgl = date('YmdHi');
        $data = array();
        $id = (int)$request->id_news > 0 ? (int)$request->id_news : 0;
        $path_img = $request->file("img");
        $data = array(            
            'title'   				=> $request->title,
            'short_description'   	=> $request->short_description,
            'description'   		=> $request->description,
        );		
        
        if (!empty($path_img)) {
            $nama = str_replace(' ', '', $path_img->getClientOriginalName());
            if (strlen($nama) > 32) $nama = substr($nama, 0, 32);
            $nama = strtolower($nama);
            $nama_file = $_tgl . '' . $nama;
            $nama_file = Crypt::encryptString($nama_file);
            $fileSize = $path_img->getSize();
            $extension = $path_img->getClientOriginalExtension();
            $imageName = $nama_file . '.' . $extension;
            $tujuan_upload = 'uploads/news';
            $_extension = array('png', 'jpg', 'jpeg');
            if ($fileSize > 2099200) { // satuan bytes
                $result = array(
                    'err_code'  => '07',
                    'err_msg'   => 'file size over 2048',
                    'data'      => $fileSize
                );
                return response($result);
                return false;
            }
            if (!in_array($extension, $_extension)) {
                $result = array(
                    'err_code'  => '07',
                    'err_msg'   => 'file extension not valid',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $path_img->move($tujuan_upload, $imageName);
            $data += array("img" => $imageName);
        }
        if ($id > 0) {
            $data += array("updated_at" => $tgl, "updated_by" => $request->id_operator);
            DB::table('news')->where('id_news', $id)->update($data);
        } else {
            $data += array("created_at" => $tgl, "created_by" => $request->id_operator);
            $id = DB::table('news')->insertGetId($data, "id_news");
        }

        if ($id > 0) {
            $data += array('id_news' => $id);
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

    function proses_delete(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id = (int)$request->id_news > 0 ? (int)$request->id_news : 0;
        $data = array("deleted_at" => $tgl, "deleted_by" => $request->id_operator);
        DB::table('news')->where('id_news', $id)->update($data);
        $result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => null
        );
        return response($result);
    }
	
	function detail(Request $request){
		$id = (int)$request->id_news > 0 ? (int)$request->id_news : 0;
		$where = ['id_news' => $id];
		$_data = DB::table('news')->select('news.*')                
                ->where($where)->first();
		unset($_data->created_by);
        unset($_data->updated_by);
        unset($_data->deleted_by);
        unset($_data->created_at);
        unset($_data->updated_at);
        unset($_data->deleted_at);
		$result = array();
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $_data
        );
        return response($result);
	}

}
