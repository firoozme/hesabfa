<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Exports\Enums\ExportFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadExport extends Controller
{
    public function __invoke(Request $request, Export $export): StreamedResponse
    {
        // dd($export->user_id,auth('company')->user()->id);
        // if (filled(Gate::getPolicyFor($export::class))) {
        //     authorize('view', $export);
        // } else {
        //     abort_unless($export->user_id != auth('company')->user()->id);
        // }
        if($export->user_id != auth('company')->user()->id){
            abort(403);
        }

        $format = ExportFormat::tryFrom($request->query('format'));

        abort_unless($format !== null, 404);

        return $format->getDownloader()($export);
    }
}
