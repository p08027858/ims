<?php

namespace App\Controllers;

use App\Services\AuthException;
use App\Services\ImportService;
use App\Support\Session;

/** CSV bulk import (Phase 8 item 2). */
final class ImportController
{
    private ImportService $imports;

    public function __construct()
    {
        $this->imports = new ImportService();
    }

    /** GET /admin/import/students loader. */
    public function pageData(array $params): array
    {
        return [
            'importResult' => Session::pullFlashData('import_result'),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function importStudents(array $params): void
    {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flashError('กรุณาเลือกไฟล์ CSV');
            header('Location: /admin/import/students');
            exit;
        }

        try {
            $result = $this->imports->importStudentsCsv($_FILES['csv_file']['tmp_name']);
            Session::flashData('import_result', $result);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/import/students');
        exit;
    }

    /** GET /admin/import/companies loader (SITEMAP.md §5 — Phase 11). */
    public function companiesPageData(array $params): array
    {
        return [
            'importResult' => Session::pullFlashData('import_result'),
            'formError' => Session::pullFlashError(),
        ];
    }

    public function importCompanies(array $params): void
    {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flashError('กรุณาเลือกไฟล์ CSV');
            header('Location: /admin/import/companies');
            exit;
        }

        try {
            $result = $this->imports->importCompaniesCsv($_FILES['csv_file']['tmp_name']);
            Session::flashData('import_result', $result);
        } catch (AuthException $e) {
            Session::flashError($e->getMessage());
        }
        header('Location: /admin/import/companies');
        exit;
    }
}
