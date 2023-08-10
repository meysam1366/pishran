<?php

namespace App\Http\Controllers;

use DateTime;
use Hekmatinasser\Jalali\Jalali;
use Hekmatinasser\Verta\Facades\Verta;
// use Hekmatinasser\Verta\Verta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HomeController extends Controller
{
    public function home()
    {
        $fromJalali = $this->convert(request()->from);
        $toJalali = $this->convert(request()->to);
        $dateFrom = explode('/', $fromJalali);
        $dateTo = explode('/', $toJalali);
        $yearFrom = $dateFrom[0];
        $monthFrom = $dateFrom[1];
        $dayFrom = $dateFrom[2];
        $yearTo = $dateTo[0];
        $monthTo = $dateTo[1];
        $dayTo = $dateTo[2];

        $fromGregorian = strtotime(implode('/', Verta::jalaliToGregorian($yearFrom, $monthFrom, $dayFrom)));
        $toGregorian = strtotime(implode('/', Verta::jalaliToGregorian($yearTo, $monthTo, $dayTo)));

        $data = [
            'email'     => "a@b.com",
            "password"  => "test@test"
        ];
        $request = Http::post("http://chart.pishrun.ir/api/login?email={$data['email']}&password={$data['password']}");
        $token = json_decode($request->body())->token;

        $kontorLog = Http::get("http://chart.pishrun.ir/api/log/read?since={$fromGregorian}&to={$toGregorian}&device_serial=62101200");
        $contents = json_decode($kontorLog->body());
        $results = [];
        $dates = [];
        foreach ($contents as $key => $content) {
            $occured_at = $content->occured_at;
            $data = json_decode($content->data);
            if ($data->Ch_number == 3) {
                $dates[date('H', strtotime($content->occured_at))] = [$content->occured_at, $data->Consumption];
                $results[] = [$occured_at, $data->Consumption];
            } else {
                continue;
            }
        }

        sort($dates);
        $dates = json_encode($dates);

        return view('home', compact('dates'));
    }

    public function convert($string)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }
}
