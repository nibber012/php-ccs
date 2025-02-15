<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportManager {
    private $database;
    private $spreadsheet;

    public function __construct() {
        $this->database = Database::getInstance();
        $this->spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }

    public function exportApplicantResults($filters = []) {
        $sheet = $this->spreadsheet->getActiveSheet();
        
        // Set headers
        $sheet->setCellValue('A1', 'Name');
        $sheet->setCellValue('B1', 'Score');
        $sheet->setCellValue('C1', 'Remarks');
        
        // Style headers
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ]
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        
        // Get data
        $query = "SELECT 
            CONCAT(a.first_name, ' ', a.last_name) as full_name,
            a.total_score,
            CASE 
                WHEN e.name = 'passed' THEN 'Passed'
                WHEN e.name = 'failed' THEN 'Failed'
                ELSE e.name 
            END as exam_status
        FROM applicants a
        LEFT JOIN exam_status e ON a.exam_status_id = e.id";
        
        if (!empty($filters)) {
            $whereClause = [];
            $params = [];
            
            if (isset($filters['status'])) {
                $whereClause[] = "e.name = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($whereClause)) {
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }
        }
        
        $query .= " ORDER BY a.total_score DESC";
        
        $stmt = $this->database->prepare($query);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add data to sheet
        $row = 2;
        foreach ($results as $result) {
            $sheet->setCellValue('A' . $row, $result['full_name']);
            $sheet->setCellValue('B' . $row, $result['total_score']);
            $sheet->setCellValue('C' . $row, $result['exam_status']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
        
        // Generate filename
        $filename = 'applicant_results_' . date('Y-m-d_His') . '.xlsx';
        $filepath = __DIR__ . '/../exports/' . $filename;
        
        // Save file
        $writer->save($filepath);
        
        return $filename;
    }
}
