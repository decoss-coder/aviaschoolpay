<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FicheClasseExport implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        private $etab,
        private $annee,
        private $classe,
        private $enseignant,
        private $eleves
    ) {}

    public function title(): string
    {
        return 'Fiche de classe';
    }

    public function array(): array
    {
        $rows = [];
        $rows[] = ['FICHE DE CLASSE'];
        $rows[] = [
            'École : ' . ($this->etab->nom ?? '—'),
            'Année : ' . ($this->annee->libelle ?? '—'),
            'Classe : ' . $this->classe->nom,
            'Prof. principal : ' . trim(($this->enseignant->nom ?? '') . ' ' . ($this->enseignant->prenom ?? '')),
        ];
        $rows[] = [];
        $rows[] = ['N°', 'MATRICULE', 'NOM ET PRÉNOM', 'SEXE', 'DATE NAISS.', 'LIEU NAISS.', 'TÉL.', 'CONTACT URGENCE'];

        $i = 1;
        foreach ($this->eleves as $eleve) {
            $rows[] = [
                $i++,
                $eleve->matricule_desps ?: ($eleve->matricule_interne ?: '—'),
                strtoupper(trim(($eleve->nom ?? '') . ' ' . ($eleve->prenom ?? ''))),
                $eleve->sexe ?? '',
                $eleve->date_naissance?->format('d/m/Y') ?? '',
                $eleve->lieu_naissance ?? '',
                $eleve->telephone ?? '',
                $eleve->contact_urgence_tel ?? '',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = 4 + $this->eleves->count();

                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']],
                ]);
                $sheet->getStyle('A1')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
                ]);

                $sheet->getStyle('A4:H4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(22);

                if ($lastRow >= 5) {
                    $sheet->getStyle("A5:H{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                    ]);
                    $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B5:B{$lastRow}")->getFont()->setBold(true);
                    $sheet->getStyle("D5:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(15);
                $sheet->getColumnDimension('C')->setWidth(32);
                $sheet->getColumnDimension('D')->setWidth(7);
                $sheet->getColumnDimension('E')->setWidth(14);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(14);
                $sheet->getColumnDimension('H')->setWidth(18);
            },
        ];
    }
}
