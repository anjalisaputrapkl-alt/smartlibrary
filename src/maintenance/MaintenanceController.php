<?php

require_once __DIR__ . '/MaintenanceModel.php';

class MaintenanceController {
  private $model;

  public function __construct($pdo) {
    $this->model = new MaintenanceModel($pdo);
  }

  /**
   * Get all maintenance records
   */
  public function getAll($limit = null, $offset = 0) {
    return $this->model->getAll($limit, $offset);
  }

  /**
   * Get maintenance records for a specific book
   */
  public function getByBook($book_id) {
    return $this->model->getByBook($book_id);
  }

  /**
   * Get a single record by ID
   */
  public function getById($id) {
    return $this->model->getById($id);
  }

  /**
   * Add new maintenance record
   */
  public function addRecord($book_id, $status, $notes = null) {
    try {
      $id = $this->model->addRecord($book_id, $status, $notes);
      return [
        'success' => true,
        'message' => 'Catatan maintenance berhasil ditambahkan',
        'id' => $id
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Update maintenance record
   */
  public function updateRecord($id, $status, $notes = null) {
    try {
      $result = $this->model->updateRecord($id, $status, $notes);
      if ($result) {
        return [
          'success' => true,
          'message' => 'Catatan maintenance berhasil diupdate'
        ];
      } else {
        return [
          'success' => false,
          'message' => 'Tidak ada data yang diupdate'
        ];
      }
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Delete maintenance record
   */
  public function deleteRecord($id) {
    try {
      $result = $this->model->deleteRecord($id);
      if ($result) {
        return [
          'success' => true,
          'message' => 'Catatan maintenance berhasil dihapus'
        ];
      } else {
        return [
          'success' => false,
          'message' => 'Catatan tidak ditemukan'
        ];
      }
    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Get count of records
   */
  public function getCount() {
    return $this->model->getCount();
  }

  /**
   * Get list of books
   */
  public function getBooks() {
    return $this->model->getBooks();
  }

  /**
   * Handle AJAX requests
   */
  public function handleAjax() {
    // Bersihkan output buffer
    if (ob_get_level() > 0) {
      ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    $action = $_POST['action'] ?? $_GET['action'] ?? null;

    if (!$action) {
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Action tidak didefinisikan']);
      exit;
    }

    try {
      switch ($action) {
        case 'add':
          $book_id = $_POST['book_id'] ?? null;
          $status = $_POST['status'] ?? null;
          $notes = $_POST['notes'] ?? null;

          if (!$book_id || !$status) {
            throw new Exception('Book ID dan Status harus diisi');
          }

          $result = $this->addRecord($book_id, $status, $notes);
          echo json_encode($result);
          break;

        case 'update':
          $id = $_POST['id'] ?? null;
          $status = $_POST['status'] ?? null;
          $notes = $_POST['notes'] ?? null;

          if (!$id || !$status) {
            throw new Exception('ID dan Status harus diisi');
          }

          $result = $this->updateRecord($id, $status, $notes);
          echo json_encode($result);
          break;

        case 'delete':
          $id = $_POST['id'] ?? null;

          if (!$id) {
            throw new Exception('ID harus diisi');
          }

          $result = $this->deleteRecord($id);
          echo json_encode($result);
          break;

        case 'get':
          $id = $_GET['id'] ?? null;
          $book_id = $_GET['book_id'] ?? null;

          if ($id) {
            $data = $this->getById($id);
          } elseif ($book_id) {
            $data = $this->getByBook($book_id);
          } else {
            $data = $this->getAll();
          }

          echo json_encode(['success' => true, 'data' => $data]);
          break;

        default:
          http_response_code(400);
          echo json_encode(['success' => false, 'message' => 'Action tidak dikenali']);
      }
    } catch (Exception $e) {
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    exit;
  }
}
?>
