<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use App\Models\User;
use PhpParser\Node\Stmt\TryCatch;

class JwtAuth{
    private $key;
    function __construct()
    {
        //ESTA ES LA LLAVE PRIVADA DE NUESTRA APP, PUEDE SER HASH O LO QUE SEA
        $this->key="aswqdfewqeddafe23ewresa";
    }

    public function getToken($email, $password) {
    $user = User::where('email', $email)->first();

    if ($user && password_verify($password, $user->password)) {
        if(empty($user->idUsuario)) {  // Cambiado de $user->id a $user->idUsuario
            return [
                'status' => 500,
                'message' => 'Error: El usuario no tiene ID válido'
            ];
        }

        $token = [
            'iss' => $user->idUsuario,  // Usa idUsuario en lugar de id
            'email' => $user->email,
            'nombre' => $user->nombre,
            'rol' => $user->rol,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 horas en lugar de 33 minutos
        ];

        $data = JWT::encode($token, $this->key, 'HS256');
    } else {
        $data = [
            'status' => 401,
            'message' => 'Datos de autenticación incorrectos'
        ];
    }

    return $data;
}
    

    //OBTIENE LA VERIFICACION DEL TOKEN Y SE OBTIENEN LOS DATOS DEL TOKEN CIFRADO
    public function checkToken($jwt,$getId=false){
        $authFlag=false;
        if(isset($jwt)){
            try{
                $decoded=JWT::decode($jwt,new Key($this->key,'HS256')); //SI NO SE DECIFRA PUEDE QUE YA EXP EL TOKEN O LANZAR EXEP
                \Log::info("Token decodificado exitosamente: " . json_encode($decoded));
            }catch(\DomainException $ex){ //LA BARRA INCLINADA JALA LA EXP DE DONDE ESTE CREADO
                \Log::error("DomainException en JWT: " . $ex->getMessage());
                $authFlag=false;
            }catch(ExpiredException $ex){
                \Log::error("Token expirado: " . $ex->getMessage());
                $authFlag=false;
            }catch(\Exception $ex){
                \Log::error("Error general en JWT: " . $ex->getMessage());
                $authFlag=false;
            }
            if(!empty($decoded)&&is_object($decoded)&&isset($decoded->email)){
                $authFlag=true;
            }
            if($getId && $authFlag){
                return $decoded;
            }
        } else {
            \Log::warning("No se recibió token JWT");
        }
        return $authFlag; //SI NO VIENE EL TOKEN SE MANDA FALSE
    }

}