<?php

namespace App\Imports;

use App\Models\Application;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class ApplicationsImport implements ToModel, WithHeadingRow, WithUpserts, WithBatchInserts
{
    public function __construct(public int $templateId, public int $resumeId)
    {
    }

    public function model(array $row): Application
    {
        $columnNames = [
            'user_id',
            'name',
            'company',
            'email',
            'phone',
            'website',
            'apply_for',
            'apply_at',
            'followup_at',
            'followup_after_days',
            'followup_freq',
            'template_id',
            'resume_id',
        ];

        $row['user_id'] = 1; //@todo add: auth()->id();
        $row['template_id'] = $this->templateId;
        $row['resume_id'] = $this->resumeId;

        if (blank($row['apply_at'])) {
            $row['apply_at'] = Carbon::now()->format('Y-m-d H:i:s');
        }

        if (blank($row['followup_after_days'])) {
            $row['followup_after_days'] = 3;
        }

        $row['followup_at'] = Carbon::parse($row['apply_at'])
            ->addDays($row['followup_after_days'])
            ->format('Y-m-d H:i:s');

        $attributes = array_intersect_key($row, array_flip($columnNames));

        return new Application($attributes);
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function uniqueBy()
    {
        return ['email', 'apply_for'];
    }
}
