<?php


namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
        $this->requireDirectorAuth(); // Garante que apenas diretores possam acessar

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $userType = $_POST['user_type'] ?? 'servidor';

            $user = new User($this->db);

            // Verificar se o e-mail já existe
            if ($user->findByEmail($email)) {
                $_SESSION['register_error'] = 'Este e-mail já está cadastrado. Tente recuperar sua senha ou use outro e-mail.';
                return ['success' => false, 'error' => $_SESSION['register_error']];
            }

            if ($user->create($name, $email, $password, $userType)) {
                $_SESSION['register_success'] = 'Usuário registrado com sucesso. Faça login para continuar.';
                return ['success' => true, 'message' => $_SESSION['register_success']];
            } else {
                $_SESSION['register_error'] = 'Erro ao registrar usuário. Por favor, tente novamente.';
                return ['success' => false, 'error' => $_SESSION['register_error']];
            }
        }

        // Se não for uma requisição POST
        return ['success' => false, 'error' => 'Método de requisição inválido.'];
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

    public function getProfileData() {
        $this->requireAuth();
        $userId = $_SESSION['user_id'];
        $user = new User($this->db);
        $profileData = $user->getProfileData($userId);

        if (!$profileData) {
            // Log do erro ou tratamento adicional
            return ['error' => 'Não foi possível obter os dados do perfil.'];
        }

        return $profileData;
    }
    public function updateProfile() {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $user = new User($this->db);

            $profileData = [
                'name' => $_POST['name'] ?? '',
                'function' => $_POST['function'] ?? '',
                'birth_date' => $_POST['birth_date'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'country' => $_POST['country'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            ];

            $result = $user->updateProfile($userId, $profileData);

            if ($result) {
                // Atualiza as informações da sessão
                $_SESSION['user_name'] = $profileData['name'];

                return [
                    'success' => true,
                    'updatedData' => $profileData
                ];
            } else {
                return ['success' => false, 'error' => 'Erro ao atualizar o perfil.'];
            }
        }
        return ['success' => false, 'error' => 'Método de requisição inválido.'];
    }

    public function updatePassword($currentPassword, $newPassword, $confirmPassword) {
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'error' => 'As novas senhas não coincidem.'];
        }

        $userId = $_SESSION['user_id'];

        // Crie uma nova instância do modelo User
        $user = new User($this->db);

        $success = $user->changePassword($userId, $currentPassword, $newPassword);

        if ($success) {
            return ['success' => true, 'message' => 'Senha atualizada com sucesso.'];
        } else {
            return ['success' => false, 'error' => 'Erro ao atualizar a senha. Verifique se a senha atual está correta.'];
        }
    }

    public function changePassword() {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($newPassword !== $confirmPassword) {
                $_SESSION['password_change_error'] = 'As novas senhas não coincidem.';
                header('Location: ' . $this->baseUrl . '/alterar-senha');
                exit;
            }

            $user = new User($this->db);
            if ($user->changePassword($userId, $currentPassword, $newPassword)) {
                $_SESSION['password_change_success'] = 'Senha alterada com sucesso.';
                header('Location: ' . $this->baseUrl . '/perfil');
                exit;
            } else {
                $_SESSION['password_change_error'] = 'Erro ao alterar a senha. Verifique se a senha atual está correta.';
                header('Location: ' . $this->baseUrl . '/alterar-senha');
                exit;
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

    // Novas funções para recuperação de senha
    public function requestPasswordReset() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = $_POST['email'] ?? '';

                if (empty($email)) {
                    return ['error' => 'Por favor, forneça um email válido.'];
                }

                $user = new User($this->db);
                $userData = $user->findByEmail($email);

                if ($userData) {
                    $resetCode = bin2hex(random_bytes(16));
                    $expiryTime = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    if ($user->saveResetCode($userData['id'], $resetCode, $expiryTime)) {
                        if ($this->sendResetEmail($email, $resetCode)) {
                            $_SESSION['reset_email'] = $email;
                            $_SESSION['reset_instruction'] = "O código para recuperação da senha foi enviado para o seu email. Por favor, verifique sua caixa de entrada, spam e lixo eletrônico.";
                            return ['success' => 'Um código de recuperação foi enviado para o seu email.', 'redirect' => true];
                        } else {
                            return ['error' => 'Ocorreu um erro ao enviar o email. Por favor, tente novamente mais tarde.'];
                        }
                    } else {
                        return ['error' => 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.'];
                    }
                } else {
                    // Por segurança, não informamos se o email existe ou não
                    return ['error' => 'Se o email estiver cadastrado, você receberá um código de recuperação.'];
                }
            }
            // Adicione um retorno padrão para quando o método não for POST
            return ['error' => 'Método de requisição inválido.'];
        } catch (Exception $e) {
            error_log('Erro na redefinição de senha: ' . $e->getMessage());
            return ['error' => 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.'];
        }
    }

    private function sendResetEmail($email, $resetCode) {
        $mail = new PHPMailer(true);

        try {
            // Verificação de DNS
            if (!checkdnsrr(explode('@', $email)[1], 'MX')) {
                error_log("Domínio de e-mail inválido: $email");
                return false;
            }

            // Configurações de debug
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer: $str");
            };

            // Configurações do servidor
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'techvoidbrasil@gmail.com'; // Mantenha o email atual
            $mail->Password   = 'sosb jojd gcbx mtpi'; // Mantenha a senha atual
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Configurações de remetente e destinatário
            $mail->setFrom('techvoidbrasil@gmail.com', 'Sistema de Produtividade');
            $mail->addReplyTo('techvoidbrasil@gmail.com', 'Suporte do Sistema de Produtividade');
            $mail->addAddress($email);

            // Conteúdo do e-mail
            $mail->isHTML(true);
            $mail->Subject = "=?UTF-8?B?" . base64_encode("Recuperação de Senha - Sistema de Produtividade") . "?=";
            $mail->Body = "
        <html>
        <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
            <h2 style='color: #4a4a4a;'>Recuperação de Senha</h2>
            <p>Olá,</p>
            <p>Recebemos uma solicitação de recuperação de senha para sua conta no Sistema de Produtividade.</p>
            <p>Seu código de recuperação é: <strong style='background-color: #f0f0f0; padding: 5px;'>{$resetCode}</strong></p>
            <p>Por favor, utilize este código na página de redefinição de senha para criar uma nova senha.</p>
            <p>Se você não solicitou esta recuperação, por favor, ignore este e-mail ou entre em contato com nosso suporte.</p>
            <p>Este código expirará em 1 hora por motivos de segurança.</p>
            <p>Atenciosamente,<br>Equipe do Sistema de Produtividade</p>
        </body>
        </html>";

            $mail->AltBody = "Olá,\n\nRecebemos uma solicitação de recuperação de senha para sua conta no Sistema de Produtividade.\n\nSeu código de recuperação é: {$resetCode}\n\nPor favor, utilize este código na página de redefinição de senha para criar uma nova senha.\n\nSe você não solicitou esta recuperação, por favor, ignore este e-mail ou entre em contato com nosso suporte.\n\nEste código expirará em 1 hora por motivos de segurança.\n\nAtenciosamente,\nEquipe do Sistema de Produtividade";

            // Tentativa de envio
            if(!$mail->send()) {
                error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
                return false;
            }
            return true;
        } catch (Exception $e) {
            error_log("Exceção ao enviar e-mail para {$email}: " . $e->getMessage());
            return false;
        }
    }

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $resetCode = $_POST['reset_code'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($resetCode) || empty($newPassword) || empty($confirmPassword)) {
                return ['error' => 'Todos os campos são obrigatórios.'];
            }

            if ($newPassword !== $confirmPassword) {
                return ['error' => 'As senhas não coincidem.'];
            }

            $user = new User($this->db);
            $result = $user->resetPassword($resetCode, $newPassword);

            if ($result) {
                unset($_SESSION['reset_email']); // Limpa o email da sessão
                return ['success' => 'Sua senha foi redefinida com sucesso. Você pode fazer login agora.'];
            } else {
                return ['error' => 'Código inválido ou expirado. Por favor, solicite um novo código.'];
            }
        }
    }


}

