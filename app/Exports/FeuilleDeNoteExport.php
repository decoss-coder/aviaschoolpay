<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FeuilleDeNoteExport implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private $etab,
        private $annee,
        private $classe,
        private $matiere,
        private $enseignant,
        private $eleves,
        private int $nbCols = 6
    ) {}

    public function title(): string
    {
        return 'Feuille de note';
    }

    public function array(): array
    {
        $rows = [];

        // R1 — titre
        $rows[] = ['FEUILLE DE NOTE'];

        // R2 — métadonnées
        $rows[] = [
            'École : ' . ($this->etab->nom ?? '—'),
            'Année : ' . ($this->annee->libelle ?? '—'),
            'Classe : ' . $this->classe->nom,
            'Matière : ' . ($this->matiere->nom ?? '—'),
            'Prof : ' . trim(($this->enseignant->nom ?? '') . ' ' . ($this->enseignant->prenom ?? '')),
        ];

        // R3 — vide
        $rows[] = [];

        // R4 — légende méta
        $legendRow = ['↓ À REMPLIR PAR L\'ENSEIGNANT POUR CHAQUE COLONNE NOTE ↓'];
        for ($i = 1; $i <= $this->nbCols; $i++) $legendRow[] = "Col {$i}";
        $rows[] = $legendRow;

        // R5 — Titre éval
        $r = ['Titre de l\'évaluation *']; for ($i=0; $i<$this->nbCols; $i++) $r[] = ''; $rows[] = $r;
        // R6 — Type
        $r = ['Type (DEVOIR / INTERRO / COMPO / TP / PROJET) *']; for ($i=0; $i<$this->nbCols; $i++) $r[] = 'DEVOIR'; $rows[] = $r;
        // R7 — Date
        $r = ['Date (YYYY-MM-DD) *']; for ($i=0; $i<$this->nbCols; $i++) $r[] = now()->toDateString(); $rows[] = $r;
        // R8 — Barème
        $r = ['Note sur (barème)']; for ($i=0; $i<$this->nbCols; $i++) $r[] = 20; $rows[] = $r;
        // R9 — Coef
        $r = ['Coefficient']; for ($i=0; $i<$this->nbCols; $i++) $r[] = 1; $rows[] = $r;
        // R10 — Trimestre
        $r = ['Trimestre (1, 2 ou 3) *']; for ($i=0; $i<$this->nbCols; $i++) $r[] = 1; $rows[] = $r;

        // R11 — vide
        $rows[] = [];

        // R12 — header colonnes élèves
        $header = ['N°', 'MATRICULE', 'NOM ET PRÉNOM', 'SEXE'];
        for ($i = 1; $i <= $this->nbCols; $i++) $header[] = "Note {$i}";
        $header[] = 'MOY';
        $rows[] = $header;

        // R13+ — élèves
        $idx = 1;
        foreach ($this->eleves as $eleve) {
            $row = [
                $idx++,
                $eleve->matricule_desps ?: ($eleve->matricule_interne ?: ''),
                trim(($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? '')),
                $eleve->sexe ?? '',
            ];
            for ($i = 0; $i < $this->nbCols; $i++) $row[] = '';
            $row[] = ''; // MOY (sera calculé par formule)
            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $nbCols = $this->nbCols;
                $lastNoteCol = Coordinate::stringFromColumnIndex(4 + $nbCols); // E=5 → start, +nbCols
                $moyCol = Coordinate::stringFromColumnIndex(4 + $nbCols + 1);
                $lastColIdx = 4 + $nbCols + 1;

                // ── Titre R1 ──
                $sheet->mergeCells("A1:{$moyCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FBBF24']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // ── Métadonnées R2 ──
                $sheet->getStyle("A2:{$moyCol}2")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
                ]);

                // ── Légende R4 ──
                $sheet->getStyle("A4:{$moyCol}4")->applyFromArray([
                    'font' => ['bold' => true, 'italic' => true, 'color' => ['rgb' => 'B45309']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF9C3']],
                ]);

                // ── Métadonnées éval R5-R10 ──
                $sheet->getStyle("A5:A10")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle("B5:{$lastNoteCol}10")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6D3D1']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // ── Header élèves R12 ──
                $sheet->getStyle("A12:{$moyCol}12")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1E40AF']]],
                ]);
                $sheet->getRowDimension(12)->setRowHeight(22);

                // ── Élèves R13+ ──
                $lastRow = 12 + $this->eleves->count();
                if ($lastRow >= 13) {
                    $sheet->getStyle("A13:{$moyCol}{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    // matricule en gras
                    $sheet->getStyle("B13:B{$lastRow}")->getFont()->setBold(true);
                    // N°, matricule, sexe : centré
                    $sheet->getStyle("A13:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D13:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // notes : centré
                    $firstNoteCol = Coordinate::stringFromColumnIndex(5);
                    $sheet->getStyle("{$firstNoteCol}13:{$moyCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Formule moyenne (col MOY)
                    for ($r = 13; $r <= $lastRow; $r++) {
                        $sheet->setCellValue("{$moyCol}{$r}", "=IFERROR(AVERAGE({$firstNoteCol}{$r}:{$lastNoteCol}{$r}),\"\")");
                    }
                    $sheet->getStyle("{$moyCol}13:{$moyCol}{$lastRow}")->getFont()->setBold(true);
                }

                // ── Largeurs colonnes ──
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(16);
                $sheet->getColumnDimension('C')->setWidth(32);
                $sheet->getColumnDimension('D')->setWidth(7);
                for ($i = 5; $i <= 4 + $nbCols; $i++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setWidth(10);
                }
                $sheet->getColumnDimension($moyCol)->setWidth(10);

                // ── Protection : matricule/nom/sexe en read-only ──
                $sheet->getProtection()->setSheet(false); // pas de password

                // Freeze panes : freeze à la ligne 13 et col E
                $firstNoteCol = Coordinate::stringFromColumnIndex(5);
                $sheet->freezePane("{$firstNoteCol}13");
            },
        ];
    }
}
