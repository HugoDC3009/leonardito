<?php

namespace Tests;

use App\Controllers\Bot;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

class TestIntent extends CIUnitTestCase
{
    public function testEsIntencionDeNuevaBusqueda()
    {
        // Mock del Bot para acceder a método privado usando reflexión o simplemente copiando la lógica para testear
        // Dado que es un método privado en el controlador, lo más fácil es usar Reflection
        
        $bot = new Bot();
        $method = new \ReflectionMethod(Bot::class, 'esIntencionDeNuevaBusqueda');
        $method->setAccessible(true);

        $casos = [
            'quiero casarme por civil' => true, // FALLA ACTUALMENTE (esperamos T, da F)
            'necesito saber los requisitos para matrimonio' => true,
            'costo' => false,
            'cual es el plazo' => false,
            'donde se paga' => false,
            'quiero saber el costo' => false, // Ojo: esto es ambiguo, pero si es costo del ACTUAL, es false
            'me gustaria casarme' => true,
            'deseo divorciarme' => true
        ];

        echo "\n--- TESTING INTENT LOGIC ---\n";
        foreach ($casos as $input => $esperado) {
            // Mockear dependencias si el método usa $this->detectarIntencionSecuencial
            // Necesitamos que el Bot real funcione, así que instanciamos
            
            $resultado = $method->invoke($bot, $input);
            $status = ($resultado === $esperado) ? "PASS" : "FAIL";
            $color = ($status === "PASS") ? "\033[32m" : "\033[31m";
            
            echo "{$color}[{$status}] Input: '{$input}' -> Got: " . ($resultado ? 'TRUE' : 'FALSE') . " (Expected: " . ($esperado ? 'TRUE' : 'FALSE') . ")\033[0m\n";
        }
    }
}

// Quick runner wrapper since we are in a raw script context often
$test = new TestIntent();
$test->testEsIntencionDeNuevaBusqueda();
