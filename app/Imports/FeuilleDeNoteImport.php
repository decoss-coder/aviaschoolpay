<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Illuminate\Support\Collection;

class FeuilleDeNoteImport implements ToCollection, WithCalculatedFormulas
{
    private array $result = ['columns' => []];

    public function collection(Collection $rows)
    {
        // Layout du template :
        //   R5  : titre éval (B..)
        //   R6  : type
        //   R7  : date
        //   R8  : note_sur
        //   R9  : coefficient
        //   R10 : trimestre
        //   R12 : header colonnes élèves (A=N°, B=Matricule, C=Nom, D=Sexe, E..=Note 1..N, dernier=MOY)
        //   R13+: élèves

        $idx = fn (int $i) => $i - 1; // 1-based → 0-based collection

        $titreRow = $rows->get($idx(5)) ?? collect();
        $typeRow  = $rows->get($idx(6)) ?? collect();
        $dateRow  = $rows->get($idx(7)) ?? collect();
        $baremeRow= $rows->get($idx(8)) ?? collect();
        $coefRow  = $rows->get($idx(9)) ?? collect();
        $trimRow  = $rows->get($idx(10)) ?? collect();
        $header   = $rows->get($idx(12)) ?? collect();

        // Nb colonnes = header columns entre D (Sexe) et MOY (exclu)
        $headerArr = $header->all();
        $nbCols = 0;
        for ($i = 4; $i < count($headerArr); $i++) {
            $v = strtoupper(trim((string) ($headerArr[$i] ?? '')));
            if ($v === 'MOY' || $v === '') break;
            $nbCols++;
        }
        if ($nbCols === 0) $nbCols = 6;

        $columns = [];
        for ($c = 0; $c < $nbCols; $c++) {
            $excelCol = 4 + $c; // 0-based : E=4, F=5, ...

            $titre = trim((string) ($titreRow->get($excelCol) ?? ''));
            if ($titre === '') continue; // colonne vide → skip

            $columns[$c] = [
                'titre'           => $titre,
                'type'            => trim((string) ($typeRow->get($excelCol) ?? 'DEVOIR')),
                'date_evaluation' => $this->parseDate($dateRow->get($excelCol)),
                'note_sur'        => is_numeric($baremeRow->get($excelCol)) ? (float) $baremeRow->get($excelCol) : 20,
                'coefficient'     => is_numeric($coefRow->get($excelCol)) ? (float) $coefRow->get($excelCol) : 1,
                'trimestre'       => (int) ($trimRow->get($excelCol) ?? 1),
                'notes'           => [],
            ];
        }

        // Lignes élèves : à partir de R13 (idx 12)
        for ($r = 12; $r < $rows->count(); $r++) {
            $row = $rows->get($r);
            if (!$row) continue;

            $matricule = trim((string) ($row->get(1) ?? ''));
            if ($matricule === '') continue;

            foreach ($columns as $c => $col) {
                $excelCol = 4 + $c;
                $val = $row->get($excelCol);
                if ($val === null || $val === '') continue;
                $columns[$c]['notes'][$matricule] = $val;
            }
        }

        $this->result['columns'] = array_values($columns);
    }

    public function result(): array
    {
        return $this->result;
    }

    private function parseDate($value): string
    {
        if ($value instanceof \DateTimeInterface) return $value->format('Y-m-d');
        if (is_numeric($value)) {
            // Excel serial date
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {}
        }
        $s = trim((string) $value);
        if ($s === '') return now()->toDateString();
        try { return \Carbon\Carbon::parse($s)->toDateString(); }
        catch (\Throwable $e) { return now()->toDateString(); }
    }
}
