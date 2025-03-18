<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\User;

class AuthController {
    private $db;
    private $baseUrl;

    public function __construct($db) {
        $this->db = $db;
        $this->baseUrl = $this->getBaseUrl();
    }

    /**
     * Obtém a URL base do sistema
     * @return string
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $dirName = dirname($scriptName);

        // Se estiver na raiz do domínio, retorna apenas o protocolo e host
        if ($dirName == '/' || $dirName == '\\') {
            return $protocol . $host;
        }

        // Remove o segmento '/public' do caminho se estiver presente
        $basePath = $protocol . $host . $dirName;
        if (strpos($basePath, '/public') !== false) {
            $basePath = substr($basePath, 0, strpos($basePath, '/public') + 7);
        }

        return $basePath;
    }

    public function requireServerAuth() {
        $this->requireAuth();
        if ($_SESSION['user_type'] !== 'servidor') {
            header('Location: ' . $this->baseUrl . '/dashboard-diretor');
            exit;
        }
    }

    public function getCurrentUser() {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $user = new User($this->db);
            return $user->findById($userId);
        }
        return null;
    }

    public function logout() {
        // Destruir todas as variáveis de sessão
        session_unset();

        // Destruir a sessão
        session_destroy();

        // Redirecionar para a página de login
        header('Location: ' . $this->baseUrl . '/login');
        exit;
    }

    public function requireDirectorAuth() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
            header('Location: ' . $this->baseUrl . '/login');
            exit;
        }
    }

    public function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->baseUrl . '/login');
            exit;
        }
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $userType = $_POST['user_type'] ?? 'servidor';

            $user = new User($this->db);
            if ($user->create($name, $email, $password, $userType)) {
                $_SESSION['register_success'] = 'Usuário registrado com sucesso.';
                header('Location: ' . $this->baseUrl . '/login');
                exit;
            } else {
                $_SESSION['register_error'] = 'Erro ao registrar usuário.';
            }
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                return ['error' => 'Email e senha são obrigatórios.'];
            }

            $user = new User($this->db);
            $userData = $user->findByEmail($email);

            if ($userData && password_verify($password, $userData['password'])) {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_name'] = $userData['name'];
                $_SESSION['user_type'] = $userData['user_type'];

                if ($userData['user_type'] === 'servidor') {
                    header('Location: ' . $this->baseUrl . '/dashboard-servidor');
                } else {
                    header('Location: ' . $this->baseUrl . '/dashboard-diretor');
                }
                exit;
            } else {
                return ['error' => 'Credenciais inválidas.'];
            }
        }
    }

    /**
     * Retorna a URL base para uso em outros arquivos
     * @return string
     */
    public function getBaseUrlForViews() {
        return $this->baseUrl;
    }
}