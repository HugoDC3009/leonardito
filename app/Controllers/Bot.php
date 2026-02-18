<?php

namespace App\Controllers;

use App\Models\TupaModel;

class Bot extends BaseController
{
    public function index()
    {
        // Limpiar memoria de conversación al cargar/recargar la página
        $session = \Config\Services::session();
        $session->remove([
            'tupa_item_actual', 
            'tupa_contexto_secciones', 
            'tupa_opciones', 
            'tupa_seccion_actual'
        ]);

        // Esta función carga la página del chat
        return view('chat_view');
    }

    public function consultar()
    {
        $model = new TupaModel();
        
        // Obtenemos la pregunta del usuario
        $mensaje = $this->request->getPost('mensaje');
        
        // DEBUG: Log the incoming message
        log_message('error', 'TUPA CONSULTAR: mensaje="' . $mensaje . '"');

        if (empty($mensaje)) {
            return $this->response->setJSON([
                'status' => 'error',
                'respuesta' => '❌ Por favor escribe tu consulta.'
            ]);
        }

        // PRIMERO: Verificar si hay contexto de secciones activas (para respuestas cortas como "viudos")
        $session = \Config\Services::session();
        $contextoSecciones = $session->get('tupa_contexto_secciones');

        if ($contextoSecciones && !empty($mensaje)) {
            // Verificar si el mensaje coincide con alguna sección del contexto actual
            $seccionSolicitada = $this->detectarSeccion($mensaje, array_keys($contextoSecciones['secciones']));
            
            if ($seccionSolicitada) {
                // Existe coincidencia, mostramos la sección y mantenemos el flujo
                $tramite = $contextoSecciones['tramite'];
                
                // Formateamos respuesta pasando el mensaje (que coincidirá con la sección)
                // DETECTAR INTENCIÓN: Si el usuario dice "costo soltero", mostrar costo. Si solo dice "soltero", mostrar requisitos.
                $intencion = $this->detectarIntencionSecuencial($mensaje);
                $seccionAmostrar = $intencion ?: 'requisitos';
                
                $respuesta = $this->formatearRespuesta($tramite, $mensaje, $seccionAmostrar);
                
                // Limpiar contexto una vez respondido (o mantenerlo si se quiere permitir "y viudos?")
                // Por ahora lo limpiamos para evitar confusiones futuras
                $session->remove('tupa_contexto_secciones');

                return $this->response->setJSON([
                    'status' => 'encontrado',
                    'respuesta' => $respuesta['texto']
                ]);
            }
        }

        // SEGUNDO: Verificar si es una selección numérica de opciones previas
        $opcionSeleccionadaIndex = $this->detectarSeleccionOpcion($mensaje);
        
        if ($opcionSeleccionadaIndex !== null) {
            $opcionSeleccionada = $opcionSeleccionadaIndex; // Ya viene 0-indexed de la función
            $opciones = $session->get('tupa_opciones');
            
            if ($opciones && isset($opciones[$opcionSeleccionada])) {
                $tramite = $opciones[$opcionSeleccionada];
                
                // Guardar como trámite actual para seguimiento secuencial
                $session->set('tupa_item_actual', $tramite);
                
                // Responder con la primera parte (Requisitos)
                $respuesta = $this->formatearRespuesta($tramite, '', 'requisitos');
                
                $session->remove('tupa_opciones');
                $session->remove('tupa_contexto_secciones');
                $session->remove('tupa_seccion_actual'); // Limpiar la sección actual al seleccionar una nueva opción

                return $this->response->setJSON([
                    'status' => 'encontrado',
                    'respuesta' => $respuesta['texto']
                ]);
            } else {
                return $this->response->setJSON([
                    'status' => 'sin_opciones',
                    'respuesta' => '❓ Para seleccionar por número, primero busca un trámite que tenga varias opciones. Por ejemplo, prueba escribiendo: <strong>matrimonio</strong>.'
                ]);
            }
        }

        // TERCERO: Verificar si el usuario pide un dato específico del TRÁMITE ACTUAL (Contextual)
        $tupaActual = $session->get('tupa_item_actual');
        // Solo considerarlo seguimiento si NO parece una búsqueda nueva formal
        // NUEVO: También excluir si el usuario quiere listar tipos (ej: "todos los tipos de licencia")
        $quiereListarTipos = $this->detectarIntencionListarTipos($mensaje);
        if ($tupaActual && $this->esPreguntaDeSeguimiento($mensaje) && !$this->esIntencionDeNuevaBusqueda($mensaje) && !$quiereListarTipos) {
            $intencion = $this->detectarIntencionSecuencial($mensaje);
            $seccionesInternas = $this->parsearSecciones($tupaActual['requisitos'] ?? '');
            $seccionSub = $this->detectarSeccion($mensaje, array_keys($seccionesInternas));
            
            if ($intencion) {
                $respuesta = $this->formatearRespuesta($tupaActual, $mensaje, $intencion);
                return $this->response->setJSON([
                    'status' => 'encontrado',
                    'respuesta' => $respuesta['texto']
                ]);
            } else if ($seccionSub) {
                $respuesta = $this->formatearRespuesta($tupaActual, $mensaje, 'requisitos');
                return $this->response->setJSON([
                    'status' => 'encontrado',
                    'respuesta' => $respuesta['texto']
                ]);
            }
        }

        // CUARTO: Detectar saludos cordiales
        $saludos = ['hola', 'buenos dias', 'buenas tardes', 'buenas noches', 'que tal', 'como estas', 'saludos'];
        $mensajeLower = strtolower(trim($mensaje));
        $mensajeClean = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensajeLower);
        
        foreach ($saludos as $saludo) {
            if (strpos($mensajeClean, $saludo) !== false && strlen($mensajeClean) < 50) {
                 return $this->response->setJSON([
                    'status' => 'saludo',
                    'respuesta' => '¡Hola! 👋 Soy el Asistente TUPA 🤖. Estoy listo para ayudarte con tus trámites. ¿Qué te gustaría consultar hoy?'
                ]);
            }
        }

        // QUINTO: Detectar si el usuario pregunta qué puede hacer el bot (Capacidades)
        $respuestaCapacidades = $this->detectarPreguntaCapacidades($mensaje);
        if ($respuestaCapacidades) {
            return $this->response->setJSON($respuestaCapacidades);
        }

        // SEXTO: Validar que la consulta esté relacionada con el TUPA
        if (!$model->esConsultaTUPA($mensaje)) {
            return $this->response->setJSON([
                'status' => 'fuera_de_contexto',
                'respuesta' => '🙋‍♂️ Lo siento, solo puedo ayudarte con consultas sobre <b>trámites y procedimientos del TUPA</b>.'
            ]);
        }

        // SEXTO: Buscar en la base de datos
        try {
            // NUEVO: Detectar si el usuario pregunta por "tipos", "clases", "opciones", etc.
            $quiereListarTipos = $this->detectarIntencionListarTipos($mensaje);
            
            // NUEVO: Detectar si el usuario busca específicamente "licencia funcionamiento"
            $mensajeLower = mb_strtolower($mensaje, 'UTF-8');
            $buscaLicenciaFuncionamiento = (
                strpos($mensajeLower, 'licencia') !== false && 
                strpos($mensajeLower, 'funcionamiento') !== false
            );
            
            $resultados = $model->buscarTramiteInteligente($mensaje);

            if (empty($resultados)) {
                $resultados = $model->groupStart()
                                    ->like('nombre_procedimiento', $mensaje)
                                    ->orLike('keywords', $mensaje)
                                    ->groupEnd()
                                    ->findAll(5);
            }

            if (!empty($resultados)) {
                // DEBUG: Verificar estado de las variables
                log_message('error', 'TUPA DEBUG: quiereListarTipos=' . ($quiereListarTipos ? 'true' : 'false') . ', buscaLicenciaFuncionamiento=' . ($buscaLicenciaFuncionamiento ? 'true' : 'false') . ', count=' . count($resultados));
                
                // NUEVO: Si el usuario pidió "tipos" o "cuáles hay", forzar mostrar múltiples opciones
                if ($quiereListarTipos && count($resultados) > 1) {
                    $resultados = $this->eliminarDuplicados($resultados);
                    return $this->mostrarOpciones($resultados);
                }
                
                // NUEVO: Si el usuario busca licencias de funcionamiento + quiere tipos, 
                // pero solo hay 1 resultado O el resultado no es licencia de funcionamiento,
                // forzar búsqueda directa de licencias
                if ($buscaLicenciaFuncionamiento && $quiereListarTipos) {
                    log_message('error', 'TUPA: Detectado búsqueda licencia + listar tipos');
                    $resultadoActual = $resultados[0] ?? null;
                    $esLicenciaReal = $resultadoActual && 
                        strpos(mb_strtolower($resultadoActual['nombre_procedimiento'], 'UTF-8'), 'licencia de funcionamiento') === 0;
                    
                    log_message('error', 'TUPA: esLicenciaReal=' . ($esLicenciaReal ? 'true' : 'false') . ', count=' . count($resultados));
                    
                    if (!$esLicenciaReal || count($resultados) == 1) {
                        log_message('error', 'TUPA: Forzando búsqueda directa de licencias');
                        // Forzar búsqueda directa de licencias de funcionamiento
                        $licencias = $model->where("LOWER(nombre_procedimiento) LIKE '%licencia de funcionamiento%'")
                                          ->where("LOWER(nombre_procedimiento) NOT LIKE '%no requieren%licencia%'")
                                          ->orderBy('LENGTH(nombre_procedimiento)', 'ASC')
                                          ->findAll(10);
                        
                        log_message('error', 'TUPA: Encontradas ' . count($licencias) . ' licencias');
                        
                        if (!empty($licencias) && count($licencias) > 1) {
                            return $this->mostrarOpciones($licencias);
                        }
                    }
                } else if ($buscaLicenciaFuncionamiento && !$quiereListarTipos) {
                    log_message('error', 'TUPA: Busca licencia pero NO quiere listar tipos');
                } else if (!$buscaLicenciaFuncionamiento && $quiereListarTipos) {
                    log_message('error', 'TUPA: Quiere listar tipos pero NO busca licencia');
                }
                
                if (count($resultados) == 1) {
                    $tramite = $resultados[0];
                    
                    // Guardar en sesión como trámite actual
                    $session->set('tupa_item_actual', $tramite);
                    $session->remove('tupa_opciones');
                    $session->remove('tupa_contexto_secciones');
                    
                    // Detectar si ya pidió algo específico en la primera consulta (ej: "precio matrimonio")
                    $intencion = $this->detectarIntencionSecuencial($mensaje);
                    // Si no pidió nada específico, por defecto mostramos requisitos
                    $seccion = $intencion ?: 'requisitos';
                    
                    $respuesta = $this->formatearRespuesta($tramite, $mensaje, $seccion);

                    return $this->response->setJSON([
                        'status' => 'encontrado',
                        'respuesta' => $respuesta['texto'],
                        'tramite' => $tramite
                    ]);
                }
                
                if (count($resultados) > 1) {
                    $resultados = $this->eliminarDuplicados($resultados);
                    if (count($resultados) == 1) {
                        $tramite = $resultados[0];
                        $session->set('tupa_item_actual', $tramite);
                        $respuesta = $this->formatearRespuesta($tramite, $mensaje, 'requisitos');
                        return $this->response->setJSON([
                            'status' => 'encontrado',
                            'respuesta' => $respuesta['texto']
                        ]);
                    }
                    return $this->mostrarOpciones($resultados);
                }
            } else {
                return $this->response->setJSON([
                    'status' => 'no_encontrado',
                    'respuesta' => '🔍 No encontré información sobre ese trámite. Intenta ser más específico.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en búsqueda TUPA: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'respuesta' => '⚠️ Ocurrió un error al procesar tu consulta.'
            ]);
        }
    }
    
    /**
     * Eliminar duplicados del array de resultados
     */
    private function eliminarDuplicados(array $resultados): array
    {
        $unicos = [];
        $ids = [];
        
        foreach ($resultados as $resultado) {
            $id = $resultado['id'];
            if (!in_array($id, $ids)) {
                $ids[] = $id;
                $unicos[] = $resultado;
            }
        }
        
        return $unicos;
    }

    /**
     * Mostrar múltiples opciones cuando hay varios procedimientos similares
     */
    private function mostrarOpciones(array $resultados): object
    {
        // Guardar opciones en sesión para selección posterior (usando CodeIgniter)
        $session = \Config\Services::session();
        $session->set('tupa_opciones', $resultados);
        // Limpiar contexto de secciones
        $session->remove('tupa_contexto_secciones');
        
        $html = "<strong>📋 Encontré varios procedimientos relacionados:</strong><br><br>";
        
        foreach ($resultados as $index => $tramite) {
            $numero = $index + 1;
            $nombreResumido = $this->resumirNombreProcedimiento($tramite['nombre_procedimiento']);
            $categoria = !empty($tramite['categoria']) ? "<span style='font-size: 0.8em; background: #e7f0ff; color: #1e54b7; padding: 2px 6px; border-radius: 4px; margin-bottom: 4px; display: inline-block;'>📂 " . $this->resumirNombreProcedimiento($tramite['categoria']) . "</span><br>" : "";
            
            $html .= "<div style='margin-bottom: 12px; padding: 8px; border-left: 3px solid #1E54B7;'>";
            $html .= $categoria;
            $html .= "<strong>{$numero}. {$nombreResumido}</strong><br>";
            $html .= "<small>💰 {$this->limpiarTextoCosto($tramite['derecho_pago'])} | ⏱️ {$tramite['plazo_atencion']}</small>";
            $html .= "</div>";
        }
        
        $html .= "<br><em>💡 Escribe solo el número (1, 2, 3...) para ver los detalles.</em>";
        
        return $this->response->setJSON([
            'status' => 'opciones',
            'respuesta' => $html
        ]);
    }
    
    /**
     * Formatear la respuesta de manera profesional con lista de requisitos
     * @param array $tramite Datos del trámite
     * @param string $mensajeUsuario Mensaje original
     * @param string $seccionAmostrar Qué sección de información mostrar (requisitos, costo, plazo, lugar, todo)
     */
    private function formatearRespuesta(array $tramite, string $mensajeUsuario = '', string $seccionAmostrar = 'todo'): array
    {
        $session = \Config\Services::session();
        // Detectar si hay secciones (ej: Matrimonio: Soltero, Viudo, etc)
        $seccionesInternas = $this->parsearSecciones($tramite['requisitos']);
        
        // 1. Priorizar sección en el mensaje actual
        $seccionSolicitada = !empty($seccionesInternas) ? $this->detectarSeccion($mensajeUsuario, array_keys($seccionesInternas)) : null;
        
        // 2. Si no hay en el mensaje, ver si hay en la sesión
        // PERO solo si no se ha cambiado de trámite (el tupa_item_actual se valida fuera)
        if (!$seccionSolicitada && $session->has('tupa_seccion_actual')) {
            $seccionSolicitada = $session->get('tupa_seccion_actual');
        }

        $pidioRequisitosExplicitamente = ($seccionAmostrar === 'requisitos');
        $mostrarTodo = ($seccionAmostrar === 'todo');

        if (!empty($seccionesInternas)) {
             if ($seccionSolicitada && isset($seccionesInternas[$seccionSolicitada])) {
                 // Guardar en sesión para persistencia permanente mientras dure el trámite
                 $session->set('tupa_seccion_actual', $seccionSolicitada);
                 
                 $tituloLimpio = trim(str_replace(['**', '###'], '', $seccionSolicitada));
                 $tramite['requisitos'] = "<strong>🔹 Requisitos para: " . strtoupper($tituloLimpio) . "</strong>\n\n" . $seccionesInternas[$seccionSolicitada];
                 $session->remove('tupa_contexto_secciones');
             } else if ($pidioRequisitosExplicitamente) {
                 // Si pidió SOLO requisitos y no especificó subtipo, mostramos el menú de sub-tipos directamente
                 return $this->generarMenuSecciones($tramite, $seccionesInternas);
             }
        }

        // Prioridad: Usar campos de la BD si la data ya fue migrada/limpiada
        if (!empty($tramite['descripcion'])) {
            $titulo = $tramite['nombre_procedimiento'];
            $descripcion = $tramite['descripcion'];
        } else {
            // Fallback: Intentar separar automáticamente si venimos de data antigua
            $datosNombre = $this->separarTituloDescripcion($tramite['nombre_procedimiento']);
            $titulo = $datosNombre['titulo'];
            $descripcion = $datosNombre['descripcion'];
        }

        $texto = "<div class='respuesta-tramite'>";
        $texto .= "<div class='tramite-titulo'>📋 <b>" . strtoupper($titulo) . "</b></div>";
        
        if (!empty($descripcion)) {
            $texto .= "<div class='tramite-descripcion' style='font-size: 0.9em; color: #555; margin-bottom: 8px; font-style: italic;'>" . ucfirst(mb_strtolower($descripcion, 'UTF-8')) . "</div>";
        }
        
        $partesMostradas = 0;

        // 1. REQUISITOS (o Menú si es 'todo' y hay secciones)
        if ($mostrarTodo || $pidioRequisitosExplicitamente) {
            if (!empty($seccionesInternas) && !$seccionSolicitada) {
                // Si es 'todo' y hay secciones pero no eligió una, mostramos el aviso de secciones
                $texto .= "<div class='tramite-seccion'>Este trámite tiene diferentes <b>requisitos</b> según el caso. Selecciona una opción:<br><br>";
                foreach ($seccionesInternas as $nombreSeccion => $contenido) {
                    $texto .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"requisitos " . strtolower($nombreSeccion) . "\")'>👉 " . ucfirst(strtolower($nombreSeccion)) . "</button><br>";
                }
                $texto .= "</div>";
                $partesMostradas++;
            } else if (!empty($tramite['requisitos']) && $tramite['requisitos'] !== 'No especificado' && $tramite['requisitos'] !== 'Consultar en mesa de partes') {
                $texto .= "<div class='tramite-seccion'><b>📄 Requisitos:</b><br>";
                $requisitos = str_replace(['\r\n', '\r'], "\n", $tramite['requisitos']);
                
                // Intento heurístico: Si no hay saltos de línea pero hay guiones o números seguidos de punto,
                // tratamos de forzar saltos de línea para el formateador
                if (strpos($requisitos, "\n") === false) {
                    $requisitos = preg_replace('/(\s+-\s+)/', "\n- ", $requisitos);
                    $requisitos = preg_replace('/(\s+\d+\.\s+)/', "\n$1", $requisitos);
                }

                if (strpos($requisitos, "\n") !== false) {
                    $items = explode("\n", $requisitos);
                    $texto .= "<ul style='margin: 5px 0; padding-left: 20px;'>" . $this->formatearRequisitosInteligente($items) . "</ul>";
                } else {
                    // Si no se pudo detectar estructura de lista, mostrar el texto COMPLETO pero con saltos de línea <br>
                    // NO usar resumirTexto aquí porque el usuario pidió ver el detalle.
                    $texto .= nl2br(trim($requisitos));
                }
                $texto .= "</div>";
                $partesMostradas++;
            }
        }

        // 2. COSTO
        if ($mostrarTodo || $seccionAmostrar === 'costo') {
            if (!empty($tramite['derecho_pago'])) {
                $costo = $this->limpiarTextoCosto($tramite['derecho_pago']);
                $texto .= "<div class='tramite-seccion'><b>💰 Costo:</b> " . nl2br(trim($costo)) . "</div>";
                $partesMostradas++;
            }
        }
        
        // 3. PLAZO
        if ($mostrarTodo || $seccionAmostrar === 'plazo') {
            if (!empty($tramite['plazo_atencion']) && $tramite['plazo_atencion'] !== 'No especificado') {
                $texto .= "<div class='tramite-seccion'><b>⏱️ Plazo de atención:</b> " . $tramite['plazo_atencion'] . "</div>";
                $partesMostradas++;
            }
        }
        
        // 4. LUGAR / ÁREA
        if ($mostrarTodo || $seccionAmostrar === 'lugar') {
            if (!empty($tramite['area'])) {
                $texto .= "<div class='tramite-seccion'><b>🏢 Área responsable:</b> " . $tramite['area'] . "</div>";
                $partesMostradas++;
            }
            if (!empty($tramite['donde_presentar'])) {
                $texto .= "<div class='tramite-seccion'><b>📍 Dónde presentar:</b> " . $tramite['donde_presentar'] . "</div>";
                $partesMostradas++;
            }
        }
        
        $texto .= "</div>";

        // AGREGAR GUÍA PARA EL USUARIO (Botones de seguimiento)
        if (!$mostrarTodo) {
            $texto .= "<div class='sugerencias-seguimiento' style='margin-top:10px;'>";
            $texto .= "<em>¿Qué más deseas saber sobre este trámite?</em><br><div style='margin-top:5px;'>";
            
            if ($seccionAmostrar !== 'requisitos') {
                $texto .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"requisitos\")'>📄 Ver Requisitos</button> ";
            }
            if ($seccionAmostrar !== 'costo') {
                $texto .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"precio\")'>💰 Ver Costo</button> ";
            }
            if ($seccionAmostrar !== 'plazo') {
                $texto .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"plazo\")'>⏱️ Ver Plazo</button> ";
            }
            if ($seccionAmostrar !== 'lugar') {
                $texto .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"lugar\")'>📍 Ubicación</button> ";
            }
            
            $texto .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"todo\")'>📋 Ver Todo</button>";
            $texto .= "</div></div>";
        } else {
            $texto .= "<br><em>¿Necesitas información sobre otro trámite?</em>";
        }
        
        return ['texto' => $texto];
    }

    /**
     * Extraer secciones del texto de requisitos si existen
     * Formato esperado: ### SECCION: NOMBRE ###
     */
    private function parsearSecciones(string $requisitos): array
    {
        $secciones = [];
        // Regex para capturar ### SECCION: NOMBRE ### contenido...
        // (?s) activa DOTALL para que . coincida con saltos de línea
        if (preg_match_all('/### SECCION: (.*?) ###(.*?)(?=### SECCION:|$)/s', $requisitos, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $nombre = trim($match[1]);
                $contenido = trim($match[2]);
                if (!empty($nombre) && !empty($contenido)) {
                    $secciones[$nombre] = $contenido;
                }
            }
        }
        return $secciones;
    }

    private function esIntencionDeNuevaBusqueda(string $mensaje): bool
    {
        $mensaje = mb_strtolower($mensaje, 'UTF-8');
        $mensaje = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensaje);

        // Si contiene palabras de "este/esta/ese/esa", probablemente es seguimiento
        $pronombresContexto = ['este', 'esta', 'esto', 'ese', 'esa', 'eso', 'aqui', 'aca'];
        foreach ($pronombresContexto as $pron) {
            if (preg_match('/\b' . $pron . '\b/i', $mensaje)) {
                return false; 
            }
        }

        // Frases de búsqueda explícita
        $frasesBusqueda = [
            'quisiera saber', 'necesito saber', 'busco el tramite', 'informacion sobre',
            'requisitos sobre el', 'requisitos del tramite', 'requisitos de el',
            'procedimiento de', 'procedimiento para', 'como hago para', 'como se hace',
            'quiero', 'me gustaria', 'deseo', 'necesito', 'estoy buscando', 'busco',
            'como puedo', 'donde puedo', 'que necesito para'
        ];
        
        foreach ($frasesBusqueda as $frase) {
            if (strpos($mensaje, $frase) !== false) {
                // EXCEPCIÓN: Si la frase de búsqueda formal viene acompañada SOLO de un atributo (costo, plazo, etc.)
                // y no de un nombre de trámite nuevo, sigue siendo seguimiento.
                $mensajeSinFrase = trim(str_replace($frase, '', $mensaje));
                // Limpiar preposiciones extra que puedan quedar al inicio
                $mensajeSinFrase = preg_replace('/^(saber|conocer|el|la|los|las|de|del|sobre|un|una)\s+/i', '', $mensajeSinFrase);
                
                // Detectar intención secuencial en lo que queda
                $intencion = $this->detectarIntencionSecuencial($mensajeSinFrase);
                
                // Si lo que queda después de la frase es un atributo o pocas palabras, NO es búsqueda nueva
                // PERO si menciona palabras clave de trámite explícitas (matrimonio, licencia, etc), SÍ es búsqueda nueva
                // Incluimos verbos reflexivos comunes (casarme, divorciarme, separarme)
                $esTramiteExplicito = preg_match('/\b(matrimonio|licencia|certificado|constancia|autorizacion|permiso|registro|divorcio|carnet|duplicado|casarme|divorciarme|separarme|separacion)\b/i', $mensajeSinFrase);

                if (!$esTramiteExplicito && $intencion && str_word_count($mensajeSinFrase) <= 3) {
                    return false;
                }
                
                return true;
            }
        }
        return false;
    }

    /**
     * Detecta si el usuario quiere ver TODOS los tipos/opciones de un trámite
     * Ej: "quiero saber todos los tipos de licencia de funcionamiento"
     *     "cuales licencias de funcionamiento hay"
     *     "que tipos de licencia existen"
     */
    private function detectarIntencionListarTipos(string $mensaje): bool
    {
        $mensaje = mb_strtolower($mensaje, 'UTF-8');
        $mensaje = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensaje);
        
        // Frases que indican que el usuario quiere ver múltiples opciones
        $frasesListar = [
            'todos los tipos',
            'todas las licencias',
            'todos los',
            'todas las',
            'cuales hay',
            'cuales son',
            'cuales existen',
            'que tipos',
            'que clases',
            'que opciones',
            'que licencias',
            'listar',
            'listado',
            'mostrar todos',
            'ver todos',
            'ver las opciones',
            'tipos de licencia',
            'clases de licencia',
            'categorias de',
            'modalidades de',
        ];
        
        foreach ($frasesListar as $frase) {
            if (strpos($mensaje, $frase) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determina si un mensaje es una pregunta de seguimiento sobre el contexto actual
     */
    private function esPreguntaDeSeguimiento(string $mensaje): bool
    {
        $mensaje = mb_strtolower($mensaje, 'UTF-8');
        $mensaje = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensaje);
        
        $palabrasClave = [
            'precio', 'costo', 'cuesta', 'pagar', 'derecho', 'monto', 'cuanto', 'valor', 'pago',
            'requisitos', 'necesito', 'papeles', 'documentos', 'llevar',
            'plazo', 'tiempo', 'demora', 'tarda', 'cuando', 'dias',
            'donde', 'lugar', 'area', 'oficina', 'presentar', 'ubicacion',
            'todo', 'completo', 'entero', 'toda', 'informacion', 'detalle', 'detalles'
        ];
        
        foreach ($palabrasClave as $palabra) {
            if (strpos($mensaje, $palabra) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Detecta qué parte de la información quiere el usuario
     */
    private function detectarIntencionSecuencial(string $mensaje): ?string
    {
        $mensaje = mb_strtolower($mensaje, 'UTF-8');
        $mensaje = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensaje);
        
        if (strpos($mensaje, 'todo') !== false || strpos($mensaje, 'completo') !== false || strpos($mensaje, 'toda') !== false || strpos($mensaje, 'informacion') !== false || strpos($mensaje, 'detalle') !== false) {
            return 'todo';
        }
        
        // Prioridad: COSTO (Keywords fuertes)
        if (strpos($mensaje, 'precio') !== false || strpos($mensaje, 'costo') !== false || strpos($mensaje, 'cuesta') !== false || strpos($mensaje, 'pago') !== false || strpos($mensaje, 'pagar') !== false || strpos($mensaje, 'monto') !== false || strpos($mensaje, 'valor') !== false || strpos($mensaje, 'tarifa') !== false) {
            return 'costo';
        }

        // Prioridad: PLAZO
        if (strpos($mensaje, 'plazo') !== false || strpos($mensaje, 'tiempo') !== false || strpos($mensaje, 'tarda') !== false || strpos($mensaje, 'demora') !== false || strpos($mensaje, 'dias') !== false || strpos($mensaje, 'cuando') !== false) {
            return 'plazo';
        }

        // Caso ambiguo: "cuanto"
        if (strpos($mensaje, 'cuanto') !== false) {
            // Si hay algo de tiempo, es plazo. Si no, asumimos costo.
            if (strpos($mensaje, 'tiempo') !== false || strpos($mensaje, 'tarda') !== false || strpos($mensaje, 'demora') !== false || strpos($mensaje, 'dias') !== false) {
                return 'plazo';
            }
            return 'costo';
        }

        if (strpos($mensaje, 'lugar') !== false || strpos($mensaje, 'donde') !== false || strpos($mensaje, 'area') !== false || strpos($mensaje, 'ubicacion') !== false || strpos($mensaje, 'oficina') !== false) {
            return 'lugar';
        }
        if (strpos($mensaje, 'requisitos') !== false || strpos($mensaje, 'necesito') !== false || strpos($mensaje, 'documentos') !== false || strpos($mensaje, 'papel') !== false) {
            return 'requisitos';
        }
        
        return null;
    }

    /**
     * Generar HTML para el menú de sub-secciones
     */
    private function generarMenuSecciones(array $tramite, array $secciones): array
    {
        $session = \Config\Services::session();
        $session->set('tupa_contexto_secciones', [
            'tramite' => $tramite,
            'secciones' => $secciones
        ]);

        $html = "<div class='respuesta-tramite'>";
        $html .= "<div class='tramite-titulo'>📋 <b>" . strtoupper($tramite['nombre_procedimiento']) . "</b></div>";
        $html .= "<div class='tramite-seccion'>Este trámite tiene diferentes requisitos según el caso. Selecciona una opción:<br><br>";
        
        foreach ($secciones as $nombreSeccion => $contenido) {
           $html .= "<button class='btn-opcion-tupa' onclick='enviarMensaje(\"requisitos " . strtolower($nombreSeccion) . "\")'>👉 " . ucfirst(strtolower($nombreSeccion)) . "</button><br>";
        }
        
        $html .= "</div></div>";
        return ['texto' => $html];
    }

    /**
     * Detecta si el usuario está seleccionando una opción numérica u ordinal
     * Retorna el índice (0-based) si encuentra una selección válida, o null si no.
     */
    private function detectarSeleccionOpcion(string $mensaje): ?int
    {
        $mensaje = trim(mb_strtolower($mensaje, 'UTF-8'));
        $mensaje = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensaje);
        
        // 1. Número directo (1, 2, 3...)
        if (preg_match('/^[1-9][0-9]*$/', $mensaje)) {
            return intval($mensaje) - 1;
        }
        
        // 2. Mapeo de ordinales y palabras clave (palabras completas)
        $ordinales = [
            'primero' => 1, 'primera' => 1, 'primer' => 1, 'uno' => 1,
            'segundo' => 2, 'segunda' => 2, 'dos' => 2,
            'tercero' => 3, 'tercera' => 3, 'tercer' => 3, 'tres' => 3,
            'cuarto' => 4, 'cuarta' => 4, 'cuatro' => 4,
            'quinto' => 5, 'quinta' => 5, 'cinco' => 5,
            'sexto' => 6, 'sexta' => 6, 'seis' => 6,
            'septimo' => 7, 'septima' => 7, 'siete' => 7,
            'octavo' => 8, 'octava' => 8, 'ocho' => 8,
            'noveno' => 9, 'novena' => 9, 'nueve' => 9,
            'decimo' => 10, 'decima' => 10, 'diez' => 10
        ];

        // 3. Buscar patrones eliminando palabras de relleno ("la opcion 1", "quiero la segunda")
        // Usamos \b para asegurar que solo borramos palabras completas
        $palabrasRelleno = 'la|el|las|los|opcion|numero|tramite|quiero|dame|ver|escojo|seleccionar|selecciono|por|favor';
        $limpio = preg_replace('/\b(' . $palabrasRelleno . ')\b/u', '', $mensaje);
        $limpio = trim(preg_replace('/\s+/', ' ', $limpio)); // Limpiar espacios extra

        // Volver a chequear si quedó un número solo
        if (preg_match('/^[1-9][0-9]*$/', $limpio)) {
            return intval($limpio) - 1;
        }

        // Chequear si quedó una palabra ordinal
        if (isset($ordinales[$limpio])) {
            return $ordinales[$limpio] - 1;
        }

        // Búsqueda más laxa: si el mensaje contiene la palabra ordinal como palabra completa
        foreach ($ordinales as $palabra => $valor) {
            if (preg_match('/\b' . $palabra . '\b/', $mensaje)) {
                return $valor - 1;
            }
        }

        return null;
    }

    /**
     * Detectar si el usuario está preguntando por una sección específica
     */
    private function detectarSeccion(string $mensaje, array $nombresSecciones): ?string
    {
        $mensaje = strtolower($mensaje);
        // Mapeo de sinónimos comunes a las secciones
        $sinonimos = [
            'viudo' => ['viudo', 'viudez', 'fallecido', 'conyugue', 'viudito', 'viudita'],
            'soltero' => ['soltero', 'solteros', 'primera vez', 'solterito', 'solterita', 'sin compromiso', 'sin compromisos'],
            'divorciado' => ['divorciado', 'divorcio', 'separado', 'divorciadito', 'divorciadita'],
            'extranjero' => ['extranjero', 'extranjeros', 'pasaporte', 'gringo'],
            'menor' => ['menor', 'menores', 'hijo', 'edad', 'chibolo', 'pequeño'],
        ];

        foreach ($nombresSecciones as $nombre) {
            $nombreLower = strtolower($nombre);
            
            // 1. Coincidencia directa con el nombre de la sección
            if (strpos($mensaje, $nombreLower) !== false) {
                return $nombre;
            }

            // 2. Coincidencia con palabras clave dentro del nombre (ej: "SOLTERO PERUANO" -> "soltero")
            $palabrasNombre = explode(' ', $nombreLower);
            foreach ($palabrasNombre as $palabra) {
                if (strlen($palabra) > 3 && strpos($mensaje, $palabra) !== false) {
                    return $nombre;
                }
            }

            // 3. Coincidencia con sinónimos mapeados
            foreach ($sinonimos as $clave => $listaSinonimos) {
                if (strpos($nombreLower, $clave) !== false) {
                    foreach ($listaSinonimos as $sinonimo) {
                        if (strpos($mensaje, $sinonimo) !== false) {
                            return $nombre;
                        }
                    }
                }
            }
        }

        return null;
    }
    /**
     * Separa el nombre del procedimiento en Título y Descripción
     */
    private function separarTituloDescripcion(string $nombre): array
    {
        $nombre = trim($nombre);
        $titulo = $nombre;
        $descripcion = '';

        // Patrones que indican el inicio de una descripción o subtítulo
        // Ej: "Licencia de Funcionamiento PARA EDIFICACIONES..."
        // Ej: "Autorización PARA LA INSTALACIÓN..."
        $patronesSeparacion = [
            '/\s+(PARA)\s+/i',
            '/\s+(CON)\s+/i',
            '/\s+(QUE)\s+/i',
            '/\s+(EN LA MODALIDAD)\s+/i',
            '/\s+(CALIFICAD[OA]S?)\s+/i',
            '/\s+(DE NIVEL)\s+/i',
            '/\s+(NIVEL DE RIESGO)\s+/i',
            '/\s*[-\(]\s*(MODALIDAD|RIESGO)/i' // Captura casos como "- MODALIDAD C" o "(RIESGO ALTO)"
        ];

        $mejorPosicion = strlen($nombre);
        $patronEncontrado = false;

        foreach ($patronesSeparacion as $patron) {
            if (preg_match($patron, $nombre, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[0][1];
                if ($pos < $mejorPosicion) {
                    $mejorPosicion = $pos;
                    $patronEncontrado = true;
                }
            }
        }

        if ($patronEncontrado) {
            $titulo = trim(substr($nombre, 0, $mejorPosicion));
            $descripcion = trim(substr($nombre, $mejorPosicion));
            
            // Limpieza extra: Si el título termina en caracteres raros
            $titulo = trim($titulo, " -:,.");
            
            // Limpieza extra: Si la descripción empieza con guión o paréntesis que no queremos
            $descripcion = ltrim($descripcion, " -:.");
        }

        return [
            'titulo' => $titulo,
            'descripcion' => $descripcion
        ];
    }

    /**
     * Resume nombres de procedimientos muy largos para que sean legibles en listas
     */
    private function resumirNombreProcedimiento(string $nombre): string
    {
        $nombreOriginal = trim($nombre);
        $nombre = mb_strtoupper($nombreOriginal, 'UTF-8');
        
        // 1. LIMPIEZA GENÉRICA DE FRASES TÉCNICAS REPETITIVAS
        $frasesAeliminar = [
            '/PARA EDIFICACIONES CALIFICADAS CON NIVEL DE RIESGO.*?(\(|$)/i',
            '/PARA CESIONARIOS CALIFICADOS CON NIVEL DE Riesgo.*?(\(|$)/i',
            '/CON EVALUACIÓN POR LA MUNICIPALIDAD.*?(\(|$)/i',
            '/CON EVALUACIÓN PREVIA POR LA COMISIÓN TÉCNICA.*?(\(|$)/i',
            '/PARA EL ESTABLECIMIENTO OBJETO DE INSPECCIÓN/i',
            '/\(CON ITSE PREVIA\)/i',
            '/\(CON ITSE POSTERIOR\)/i',
            '/\(HASTA 2 MESES\)/i',
            '/PARA TODAS LAS MODALIDADES:.*?(\(|$)/i',
            '/MODALIDAD [A-D]:/i'
        ];
        
        foreach ($frasesAeliminar as $patron) {
            $nombre = preg_replace($patron, '', $nombre);
        }

        // 2. CASOS ESPECÍFICOS MEJORADOS
        if (stripos($nombre, 'LICENCIA DE FUNCIONAMIENTO') !== false) {
            $riesgo = '';
            // Extraer el riesgo de forma limpia
            if (preg_match('/RIESGO\s+(BAJO|MEDIO|MUY ALTO|ALTO)/i', $nombreOriginal, $m)) {
                $riesgo = ' (' . ucfirst(strtolower($m[1])) . ')';
            }
            
            if (stripos($nombre, 'CORPORATIVA') !== false) {
                return "Licencia Corporativa (Mercados/Galerías)" . $riesgo;
            }
            if (stripos($nombre, 'CESIONARIOS') !== false) {
                return "Licencia para Cesionarios" . $riesgo;
            }
            if (stripos($nombre, 'DUPLICADO') !== false) return 'Duplicado de Licencia';
            if (stripos($nombre, 'TRANSFERENCIA') !== false) return 'Transferencia de Licencia';
            if (stripos($nombre, 'CESE') !== false) return 'Cese de Actividades';
            
            // Revertir "Estándar" a nombre completo pero limpio
            return "Licencia de Funcionamiento" . $riesgo;
        }


        if (stripos($nombre, 'MATRIMONIO CIVIL') !== false) {
             // Clean extra parenthesis content if it's too long or redundant
             if (stripos($nombre, '(DENTRO DEL HORARIO') !== false) return "Matrimonio Civil (Horario Municipal)";
             if (stripos($nombre, '(EN LOCAL PÚBLICO') !== false) return "Matrimonio Civil (Local Público)";
             if (stripos($nombre, '(FUERA DEL HORARIO') !== false) return "Matrimonio Civil (Fuera de Horario)";
             if (stripos($nombre, '(A DOMICILIO') !== false) return "Matrimonio Civil (A Domicilio)";
             if (stripos($nombre, '(CEREMONIA OFICIADA') !== false) return "Matrimonio Civil (Con Alcalde)";
             if (stripos($nombre, 'COMUNITARIO') !== false) return "Matrimonio Civil Comunitario";
             
             return "Matrimonio Civil"; // Fallback to simple name if no specific variant matched perfectly or for generic cases
        }

        // 3. RECORTAR Y DAR FORMATO AMIGABLE
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
        
        // Si después de la limpieza sigue siendo muy largo, recortar
        if (mb_strlen($nombre) > 60) {
            $nombre = mb_substr($nombre, 0, 57) . '...';
        }

        // 4. CAPITALIZACIÓN INTELIGENTE (Title Case)
        $nombre = mb_convert_case($nombre, MB_CASE_TITLE, "UTF-8");
        
        // Corregir preposiciones que quedaron en mayúscula o título innecesario
        $preposiciones = [' De ', ' Del ', ' La ', ' El ', ' En ', ' Y ', ' Por ', ' Para ', ' A ', ' Con '];
        $correcciones  = [' de ', ' del ', ' la ', ' el ', ' en ', ' y ', ' por ', ' para ', ' a ', ' con '];
        $nombre = str_replace($preposiciones, $correcciones, $nombre);
        
        return ucfirst($nombre); // Asegurar primera mayúscula
    }

    private function resumirTexto(string $texto, int $maxLength = 200): string
    {
        if (strlen($texto) <= $maxLength) {
            return $texto;
        }
        
        $resumido = substr($texto, 0, $maxLength);
        $ultimoEspacio = strrpos($resumido, ' ');
        
        if ($ultimoEspacio !== false) {
            $resumido = substr($resumido, 0, $ultimoEspacio);
        }
        
        return $resumido . '...';
    }

    /**
     * Endpoint para obtener sugerencias de trámites
     */
    public function sugerencias()
    {
        $model = new TupaModel();
        $sugerencias = $model->getSugerencias();
        
        return $this->response->setJSON([
            'status' => 'success',
            'sugerencias' => $sugerencias
        ]);
    }

    /**
     * Endpoint para obtener categorías disponibles
     */
    public function categorias()
    {
        $model = new TupaModel();
        $categorias = $model->getAllCategorias();
        
        return $this->response->setJSON([
            'status' => 'success',
            'categorias' => $categorias
        ]);
    }
    /**
     * Formateador inteligente para listas de requisitos complejas
     * Detecta encabezados y agrupa ítems
     */
    private function formatearRequisitosInteligente(array $items): string
    {
        $html = "";
        $lastWasHeader = false;
        
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) continue;
            
            // FILTRAR BASURA o cortados
            // "EF", "F", "E", "EF y modificatorias", "y modificatorias"
            if (preg_match('/^(ef|f|e|ef\s+y\s+modificatorias|y\s+modificatorias|[\d\s\-]+ef)$/i', $item)) continue;
            // Permitir items cortos si son letras de opción como "A.-"
            if (strlen($item) < 3 && !preg_match('/^[A-Z][\.\-]/i', $item)) continue;
            if (preg_match('/^(pago de derecho|derecho de pago)/i', $item) && strlen($item) < 20) continue; 

            // Detectar y formatear URL de formularios
            if (preg_match('/(Formulario.*?)?(http[^\s]+)/i', $item, $matches)) {
                $url = $matches[2];
                $textoLink = !empty($matches[1]) ? str_replace(':', '', trim($matches[1])) : "Descargar Formulario";
                if (strlen($textoLink) < 5) $textoLink = "Descargar Formulario";
                
                $html .= "<div style='margin-top:5px; margin-bottom:10px;'>
                            <a href='{$url}' target='_blank' class='btn-descarga-tupa'>
                                📥 {$textoLink}
                            </a>
                         </div>";
                continue;
            } 

            // DETECCIÓN DE ENCABEZADOS PRINCIPALES (TIPO: "A.- TÍTULO")
            // Detectar "A.-", "B.-", o "1.-" seguido de texto, PERO distinguirlo de un item común
            $isExplicitSectionHeader = false;
            
            // Caso 1: Letra mayúscula punto guión obligatorios "A.- TEXTO"
            if (preg_match('/^([A-Z])[\.\-]+\s+(.*)/', $item, $matches)) {
                // Si el resto del texto es mayúscula o tiene longitud de título
                $contenido = trim($matches[2]);
                // Asumimos que "Letter.-" es casi siempre un header de sección en TUPAs
                $isExplicitSectionHeader = true;
                $itemLimpio = $item; // Mantenemos el "A.-" para que se vea la jerarquía
            }
            // Caso 2: Texto todo en mayúsculas corto/mediano que no es item numérico simple
            else if (preg_match('/^[^a-z]{4,}$/', $item) && !preg_match('/^\d+[\.\-]/', $item)) {
                // "ULTERIOR", "POR MUTUO ACUERDO"
                if (strlen($item) < 60) {
                    $isExplicitSectionHeader = true;
                    $itemLimpio = $item;
                }
            } else {
                // LIMPIEZA DE ITEMS NORMALES
                // Quitar numeración "1.-", "1.", "2 - " o letras "a)", "a.-" iniciales
                $itemLimpio = preg_replace('/^(\d+|[a-z])[\.\)\-]+\s*/i', '', $item);
                $itemLimpio = preg_replace('/^[\-\*\•\·]\s*/', '', $itemLimpio);
                $itemLimpio = ucfirst(trim($itemLimpio));
            }

            if (empty($itemLimpio)) continue;

            if ($isExplicitSectionHeader) {
                // Header (Negrita oscuro y fondo tenue)
                $html .= "<div style='margin-top:12px; margin-bottom:6px; color:#1e3a8a; font-weight:800; background-color:#eff6ff; padding:4px 8px; border-radius:4px; border-left: 3px solid #3b82f6;'>{$itemLimpio}</div>";
                $lastWasHeader = true;
            } else {
                // Item de lista
                // Detectar sub-item visual (indentacion)
                $clase = ($lastWasHeader) ? '' : ''; // Podríamos indentar más si quisiéramos
                $html .= "<li style='color:#334155; margin-bottom:4px;'>{$itemLimpio}</li>";
                $lastWasHeader = false;
            }
        }
        
        return $html;
    }

    private function limpiarTextoCosto(string $texto): string
    {
        if (empty($texto)) return "";
        
        // Limpieza común para que los costos no tengan basura tipográfica
        $texto = str_replace(['**', '\n', '\r'], ['', "\n", ''], $texto);
        // Quitar artefactos como "• . : " o similares
        $texto = preg_replace('/[•\s]*\.\s*:\s*/m', '', $texto);
        // Quitar viñetas vacías o puntos al inicio de línea seguidos de nada
        $texto = preg_replace('/^\s*[•\-\.]\s*$/m', '', $texto);
        
        return trim($texto);
    }

    /**
     * Detectar si el usuario pregunta qué puede hacer el bot
     */
    private function detectarPreguntaCapacidades(string $mensaje): ?array
    {
        $mensaje = mb_strtolower($mensaje, 'UTF-8');
        $mensaje = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $mensaje);
        
        $frasesClave = [
            'que sabes', 'que haces', 'para que sirves', 'quien eres',
            'que procedimientos', 'que tramites', 'cuales proced', 'cuales tramites',
            'lista de tramites', 'lista de procedimientos',
            'que puedo preguntar', 'sobre que', 'ayuda', 'opciones',
            'no se que buscar', 'dime algo', 'ejemplos',
            'temas', 'categorias'
        ];

        foreach ($frasesClave as $frase) {
            if (strpos($mensaje, $frase) !== false) {
                $html = "<strong>🤖 Soy el Asistente TUPA y puedo ayudarte con:</strong><br><br>";
                $html .= "<ul style='margin: 0; padding-left: 20px;'>";
                $html .= "<li>💍 <b>Matrimonio Civil</b> (solteros, divorciados, extranjeros)</li>";
                $html .= "<li>🏗️ <b>Licencias de Edificación</b> y Construcción</li>";
                $html .= "<li>🏪 <b>Licencias de Funcionamiento</b> para negocios</li>";
                $html .= "<li>📜 <b>Constancias</b> de posesión, morada, etc.</li>";
                $html .= "<li>🚗 <b>Vehículos Menores</b> y transporte</li>";
                $html .= "<li>♻️ <b>Recicladores</b> y residuos sólidos</li>";
                $html .= "<li>🐕 <b>Registro Canino</b></li>";
                $html .= "</ul>";
                $html .= "<br><em>💡 Prueba escribiendo: \"requisitos para matrimonio\" o \"cuánto cuesta la licencia de funcionamiento\".</em>";

                return [
                    'status' => 'capacidades',
                    'respuesta' => $html
                ];
            }
        }

        return null;
    }
}
