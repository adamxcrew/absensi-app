<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Permission;
use App\Models\Presence;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $attendances = Attendance::query()
            // ->with('positions')
            ->forCurrentUser(auth()->user()->position_id)
            ->get()
            ->sortByDesc('data.is_end')
            ->sortByDesc('data.is_start');

        return view('home.index', [
            "title" => "Beranda",
            "attendances" => $attendances
        ]);
    }

    public function show(Attendance $attendance)
    {
        $presences = Presence::query()
            ->where('attendance_id', $attendance->id)
            ->where('user_id', auth()->user()->id)
            ->get();

        $isHasEnterToday = $presences
            ->where('presence_date', now()->toDateString())
            ->isNotEmpty();

        $isTherePermission = Permission::query()
            ->where('permission_date', now()->toDateString())
            ->where('attendance_id', $attendance->id)
            ->where('user_id', auth()->user()->id)
            ->first();

        $data = [
            'is_has_enter_today' => $isHasEnterToday, // sudah absen masuk
            'is_not_out_yet' => $presences->where('presence_out_time', null)->isNotEmpty(), // belum absen pulang
            'is_there_permission' => (bool) $isTherePermission,
            'is_permission_accepted' => $isTherePermission?->is_accepted ?? false
        ];

        $holiday = $attendance->data->is_holiday_today ? Holiday::query()
            ->where('holiday_date', now()->toDateString())
            ->first() : false;

        // dd($data);
        return view('home.show', [
            "title" => "Informasi Absensi Kehadiran",
            "attendance" => $attendance,
            "data" => $data,
            "holiday" => $holiday
        ]);
    }

    public function permission(Attendance $attendance)
    {
        return view('home.permission', [
            "title" => "Form Permintaan Izin",
            "attendance" => $attendance
        ]);
    }
}
