<?php

namespace App\Http\Controllers;

use App\Models\Members;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
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

    public function index(Request $request)
    {
        $per_page = (int)$request->per_page > 0 ? (int)$request->per_page : 0;
        $type = (int)$request->type > 0 ? (int)$request->type : 0;
        $keyword = !empty($request->keyword) ? strtolower($request->keyword) : '';
        $sort_column = !empty($request->sort_column) ? $request->sort_column : 'nama';
        $sort_order = !empty($request->sort_order) ? $request->sort_order : 'ASC';
        $page_number = (int)$request->page_number > 0 ? (int)$request->page_number : 1;
        $where = array();
        $where = array('deleted_at' => null);
        if ((int)$type > 0) {
            $where += array('type' => $type);
        }
        $count = 0;
        $data = null;
        if (!empty($keyword)) {
            $data = DB::table('members')->where($where)->whereRaw("LOWER(nama) like '%" . $keyword . "%'")->get()->toArray();
            $count = count($data);
        } else {
            $count = Members::where($where)->count();
            //$count = count($ttl_data);
            $per_page = $per_page > 0 ? $per_page : $count;
            $offset = ($page_number - 1) * $per_page;
            $data = Members::where($where)->offset($offset)->limit($per_page)->orderBy($sort_column, $sort_order)->get();
        }
        $result = array();
        $result = array(
            'err_code'      => '04',
            'err_msg'       => 'data not found',
            'total_data'    => $count,
            'data'          => null
        );
        if ((int)$count > 0) {
            foreach ($data as $d) {
                $_data[] = $d;
            }

            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'total_data'    => $count,
                'data'      => $_data
            );
        }
        return response($result);
    }

    function detail(Request $request)
    {
        $id_member = (int)$request->id_member;
        $where = ['deleted_at' => null, 'id_member' => $id_member];
        $count = Members::where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => $id_member
        );
        if ((int)$count > 0) {

            Helper::last_login($id_member);
            $data = Members::where($where)->first();
            $photo = !empty($data->photo) ? env('APP_URL') . '/api_mekar/uploads/members/' . $data->photo : '';
            $data->photo = $photo;
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        }
        return response($result);
    }

    function reg(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = new Members();
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->nama = $request->nama;
        $data->type = (int)$request->type ? (int)$request->type : 1;
        $data->asal_bpd = $request->asal_bpd;
        $data->gembala = $request->gembala;
        $data->gereja_lokal = $request->gereja_lokal;
        $data->status = 1;
        $verify_code = rand(1000, 9999);

        $data->pass = Crypt::encryptString(strtolower($request->pass));
        $data->created_at = $tgl;
        $data->updated_at = $tgl;
        $result = array();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'not found',
            'data'      => null
        );
        if (empty($data->email)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $result = array(
                'err_code'    => '06',
                'err_msg'    => 'email invalid format',
                'data'      => null
            );
            return response($result);
            return false;
        }

        if (empty($data->phone)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'phone is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $count = 0;

        $where = ['deleted_at' => null, 'email' => $data->email];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'email already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $where = ['deleted_at' => null, 'phone' => $data->phone];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'phone already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }

        $save = $data->save();
        // if ($save) {
            // $setting = DB::table('setting')->get()->toArray();
            // $out = array();
            // if (!empty($setting)) {
                // foreach ($setting as $val) {
                    // $out[$val->setting_key] = $val->setting_val;
                // }
            // }
			// $id_member = Crypt::encryptString($data->id_member);
			// $verify_link = '';
			// $verify_link = env('APP_URL') . '/api_mekar/verify_email/'.$id_member;
            // $content_member = $out['content_reg'];
            // $content = str_replace('[#name#]', $data->nama, $content_member);
            // $content = str_replace('[#verify_link#]', $verify_link, $content);
            // $data->content = $content;
            // Mail::send([], ['users' => $data], function ($message) use ($data) {
                // $message->to($data->email, $data->nama)->subject('Register')->setBody($data->content, 'text/html');
            // });
        // }
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $data
        );
        return response($result);
    }
	
	function add_nia(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $data = new Members();
        $data->nia = $request->nia;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->nama = $request->nama;
        $data->type = (int)$request->type ? (int)$request->type : 1;
        $data->asal_bpd = $request->asal_bpd;
        $data->gembala = $request->gembala;
        $data->gereja_lokal = $request->gereja_lokal;
        $data->status = 1;
        $verify_code = rand(1000, 9999);

        $data->pass = Crypt::encryptString(strtolower($request->pass));
        $data->created_at = $tgl;
        $data->updated_at = $tgl;
        $result = array();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'not found',
            'data'      => null
        );
		if (empty($data->nia)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'nia is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($data->email)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $result = array(
                'err_code'    => '06',
                'err_msg'    => 'email invalid format',
                'data'      => null
            );
            return response($result);
            return false;
        }

        if (empty($data->phone)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'phone is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $count = 0;
		
		$where = ['deleted_at' => null, 'nia' => $data->nia];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'nia already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }

        $where = ['deleted_at' => null, 'email' => $data->email];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'email already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $where = ['deleted_at' => null, 'phone' => $data->phone];
        $count = Members::where($where)->count();
        if ($count > 0) {
            $result = array(
                'err_code'  => '05',
                'err_msg'   => 'phone already exist',
                'data'      => null
            );
            return response($result);
            return false;
        }

        $save = $data->save();
        if ($save) {
            $setting = DB::table('setting')->get()->toArray();
            $out = array();
            if (!empty($setting)) {
                foreach ($setting as $val) {
                    $out[$val->setting_key] = $val->setting_val;
                }
            }
			
            $content_member = $out['content_reg'];
            $content = str_replace('[#name#]', $data->nama, $content_member);
            $content = str_replace('[#nia#]', $data->nia, $content);
            $content = str_replace('[#password#]', $request->pass, $content);
            $data->content = $content;
            Mail::send([], ['users' => $data], function ($message) use ($data) {
                $message->to($data->email, $data->nama)->subject('Info Akun Anggota')->setBody($data->content, 'text/html');
            });
        }
        $result = array(
            'err_code'  => '00',
            'err_msg'   => 'ok',
            'data'      => $data
			
        );
        return response($result);
    }

    function edit(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        Helper::last_login($id_member);
        $result = array();
        if ($id_member > 0) {
            $data = Members::where('id_member', $id_member)->first();
            $data->asal_bpd = $request->asal_bpd;
            $data->gereja_lokal = $request->gereja_lokal;
            $data->gembala = $request->gembala;
            $data->updated_at = $tgl;
            $data->updated_by = $id_member;
            $data->save();
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id_member required',
                'data'      => null
            );
        }
        return response($result);
    }

    function login_member(Request $request)
    {
        $count = 0;
        $email = $request->email;
        $nia = $request->nia;
        $pass = strtolower($request->pass);
        $result = array();
        if (empty($email) && empty($nia)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'nia or email is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($pass)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'password is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $where = ['deleted_at' => null, 'email' => $email, 'type' => 1];
        if (!empty($nia)) {
            $where = ['deleted_at' => null, 'nia' => $nia, 'type' => 2];
        }

        $count = Members::where($where)->count();
        $result = array(
            'err_code'  => '04',
            'err_msg'   => 'data not found',
            'data'      => null
        );
        if ($count > 0) {
            $data = Members::where($where)->first();
            $password = Crypt::decryptString($data->pass);
            if ($pass == $password) {
                unset($data->pass);
                Helper::last_login($data->id_member);
                //$data->pass = $password;
                $result = array(
                    'err_code'  => '00',
                    'err_msg'   => 'ok',
                    'data'      => $data
                );
            } else {
                $result = array(
                    'err_code'  => '03',
                    'err_msg'   => 'password not match',
                    'data'      => null
                );
            }
            if ((int)$data->status != 1) {
                $result = array();
                $result = array(
                    'err_code'  => '05',
                    'err_msg'   => 'akun belum diverifikasi',
                    'data'      => null
                );
            }
        }
        return response($result);
    }

    function change_pass(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        Helper::last_login($id_member);
        $new_pass = $request->new_pass;
        $old_pass = $request->old_pass;
        $result = array();
        if (empty($new_pass)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'new_pass is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($old_pass)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'old_pass is required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if ($id_member > 0) {
            $data = Members::where('id_member', $id_member)->first();
            $password = Crypt::decryptString($data->pass);
            $old_pass = strtolower($old_pass);
            if ($password != $old_pass) {
                $result = array(
                    'err_code'  => '03',
                    'err_msg'   => 'old_pass not match',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $new_pass = strtolower($new_pass);
            if ($password == $new_pass) {
                $result = array(
                    'err_code'  => '02',
                    'err_msg'   => 'new_pass sama dengan password sebelumnya',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $data->pass = Crypt::encryptString($new_pass);
            $data->updated_at = $tgl;
            $data->updated_by = $id_member;
            $data->save();
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id_member required',
                'data'      => $id_member
            );
        }
        return response($result);
    }

    function upl_photo(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = (int)$request->id_member;
        $photo = $request->file("photo");
        $result = array();
        if ($id_member <= 0) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'id_member required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        if (empty($photo)) {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'photo required',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $_tgl = date('YmdHi');
        $data = Members::where('id_member', $id_member)->first();
        $nama = str_replace(' ', '', $data->name);
        if (strlen($nama) > 32) $nama = substr($nama, 0, 32);
        $nama = strtolower($nama);
        $nama_file = $_tgl . '' . $nama;
        $nama_file = Crypt::encryptString($nama_file);
        $fileSize = $photo->getSize();
        $extension = $photo->getClientOriginalExtension();
        $imageName = $nama_file . '.' . $extension;
        $tujuan_upload = 'uploads/members';
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
        $photo->move($tujuan_upload, $imageName);
        $data->photo = $imageName;
        $data->updated_at = $tgl;
        $data->updated_by = $id_member;
        $data->save();
        $result = array(
            'err_code'      => '00',
            'err_msg'       => 'ok',
            'data'          => $data,
            'fileSize'      => $fileSize,
            'extension'     => $extension,
            'imageName'     => $imageName,
        );
        Helper::last_login($id_member);
        return response($result);
    }



    function forgot_pass(Request $request)
    {
        $tgl = date('Y-m-d H:i:s');
        $email = $request->email;
        if (!empty($email)) {
            $data = Members::whereRaw("LOWER(email) = '" . strtolower($email) . "'")->first();
            if ((int)$data->verify_email <= 0) {
                $result = array(
                    'err_code'  => '07',
                    'err_msg'   => 'email belum terverifikasi',
                    'data'      => null
                );
                return response($result);
                return false;
            }
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            $new_pass = implode($pass);
            $data->pass = Crypt::encryptString(strtolower($new_pass));
            $data->updated_at = $tgl;
            $data->save();
            $setting = DB::table('setting')->get()->toArray();
            $out = array();
            if (!empty($setting)) {
                foreach ($setting as $val) {
                    $out[$val->setting_key] = $val->setting_val;
                }
            }
            $content_member = $out['content_forgotPass'];
            $content = str_replace('[#name#]', $data->nama, $content_member);
            $content = str_replace('[#email#]', $data->email, $content);
            $content = str_replace('[#new_pass#]', $new_pass, $content);
            $data->content = $content;
            Mail::send([], ['users' => $data], function ($message) use ($data) {
                $message->to($data->email, $data->nama)->subject('Forgot Password')->setBody($data->content, 'text/html');
            });
            $result = array(
                'err_code'  => '00',
                'err_msg'   => 'ok',
                'data'      => $data
            );
        } else {
            $result = array(
                'err_code'  => '06',
                'err_msg'   => 'email required',
                'data'      => null
            );
        }
        return response($result);
    }
	
	function verify_email($id)
    {
        $tgl = date('Y-m-d H:i:s');
        $id_member = Crypt::decryptString($id);
		
        $id_member = (int)$id_member;
        $data = Members::where('id_member', $id_member)->first();
		Log::info($data);
        if ((int)$data->status == 1) {
            $result = array(
                'err_code'  => '03',
                'err_msg'   => 'email sudah terverifikasi sebelumnya',
                'data'      => null
            );
            return response($result);
            return false;
        }
        $data->status = 1;
        $data->updated_at = $tgl;
        $data->updated_by = $id_member;
        $data->save();
        $result = array();
        $result = array(
            'err_code'      => '00',
            'err_msg'       => 'ok',
            'data'          => $data
        );
        return response($result);
    }
	
	function test_mail()
    {

        Mail::raw('mail text', function ($message) {
            $message->to('hanssn88@gmail.com', 'CNI')->subject('Test Mail CNI');
        });
    }


    //
}
