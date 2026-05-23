<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class CahierAppelImport implements ToCollection, WithCalculatedFormulas
{
    private array $rows = [];

    public function __construct(
        private Carbon $semaine,
        private Collection $eleves
    ) {}

    public function collection(Collection $sheet)
    {
        // Layout : header ligne 6 (0-based : 5), élèves à partir ligne 7 (0-based : 6)
        // Colonnes : 0=N°, 1=Matricule, 2=Nom, 3=Sexe, 4..9 = jours (Lun..Sam), 10=Total

        $rowsCount = $sheet->count();

        // Map des statuts détectés
        $map = [
            'P' => 'present',  'PRESENT'  => 'present', '✓' => 'present', '+' => 'present',
            'A' => 'absent',   'ABSENT'   => 'absent',  'X' => 'absent',  '-' => 'absent',
            'R' => 'retard',   'RETARD'   => 'retard',
            'E' => 'excuse',   'EXCUSE'   => 'excuse',  'EXCUSÉ' => 'excuse',
            'D' => 'dispense', 'DISPENSE' => 'dispense','DISPENSÉ' => 'dispense',
        ];

        for ($r = 6; $r < $rowsCount; $r++) {
            $row = $sheet->get($r);
            if (!$row) continue;

            $matricule = trim((string) ($row->get(1) ?? ''));
            if ($matricule === '') continue;

            for ($d = 0; $d < 6; $d++) {
                $colIdx = 4 + $d;
                $val = strtoupper(trim((string) ($row->get($colIdx) ?? '')));
                if ($val === '') continue;

                $statut = $map[$val] ?? null;
                if (!$statut) continue;

                $date = $this->semaine->copy()->addDays($d)->toDateString();
                $this->rows[] = [
                    'matricule' => $matricule,
                    'date'      => $date,
                    'periode'   => 'journee',
                    'statut'    => $statut,
                ];
            }
        }
    }

    public function result(): array
    {
        return $this->rows;
    }
}
