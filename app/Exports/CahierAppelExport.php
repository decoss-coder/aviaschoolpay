<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CahierAppelExport implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private $etab,
        private $classe,
        private $enseignant,
        private $eleves,
        private Carbon $semaine
    ) {}

    public function title(): string { return 'Cahier d\'appel'; }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['CAHIER D\'APPEL — Semaine du ' . $this->semaine->format('d/m/Y')];
        $rows[] = [
            'École : ' . ($this->etab->nom ?? '—'),
            'Classe : ' . $this->classe->nom,
            'Enseignant : ' . trim(($this->enseignant->nom ?? '') . ' ' . ($this->enseignant->prenom ?? '')),
        ];
        $rows[] = [];
        $rows[] = ['Légende : P=Présent · A=Absent · R=Retard · E=Excusé · D=Dispensé (laissez vide si non concerné)'];
        $rows[] = [];

        // Headers
        $header = ['N°', 'MATRICULE', 'NOM ET PRÉNOM', 'SEXE'];
        for ($i = 0; $i < 6; $i++) {
            $d = $this->semaine->copy()->addDays($i);
            $header[] = $d->locale('fr')->isoFormat('ddd D/MM');
        }
        $header[] = 'Total Abs';
        $rows[] = $header;

        $idx = 1;
        foreach ($this->eleves as $eleve) {
            $row = [
                $idx++,
                $eleve->matricule_desps ?: ($eleve->matricule_interne ?: ''),
                trim(($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? '')),
                $eleve->sexe ?? '',
            ];
            for ($i = 0; $i < 6; $i++) $row[] = '';
            $row[] = '';
            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $nbCols = 4 + 6 + 1; // N°, matricule, nom, sexe, 6 jours, total
                $lastCol = Coordinate::stringFromColumnIndex($nbCols); // K
                $headerRow = 6;
                $lastRow = $headerRow + $this->eleves->count();

                // Titre
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Légende
                $sheet->mergeCells("A4:{$lastCol}4");
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAF5FF']],
                ]);

                // Headers tableau
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(22);

                // Cells élèves
                if ($lastRow > $headerRow) {
                    $sheet->getStyle("A" . ($headerRow+1) . ":{$lastCol}{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getStyle("A" . ($headerRow+1) . ":A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B" . ($headerRow+1) . ":B{$lastRow}")->getFont()->setBold(true);
                    $sheet->getStyle("D" . ($headerRow+1) . ":{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Formule total absences (colonne K)
                    $firstJ = Coordinate::stringFromColumnIndex(5);
                    $lastJ  = Coordinate::stringFromColumnIndex(10);
                    for ($r = $headerRow + 1; $r <= $lastRow; $r++) {
                        $sheet->setCellValue("{$lastCol}{$r}",
                            "=COUNTIF({$firstJ}{$r}:{$lastJ}{$r},\"A\")");
                    }
                    $sheet->getStyle("{$lastCol}" . ($headerRow+1) . ":{$lastCol}{$lastRow}")->getFont()->setBold(true);
                }

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(7);
                for ($i = 5; $i <= 10; $i++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(11);
                }
                $sheet->getColumnDimension($lastCol)->setWidth(10);

                $sheet->freezePane('E' . ($headerRow + 1));
            },
        ];
    }
}
