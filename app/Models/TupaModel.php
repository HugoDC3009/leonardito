<?php

namespace App\Models;

use CodeIgniter\Model;

class TupaModel extends Model
{
    protected $table      = 'tramites_tupa';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'codigo', 'nombre_procedimiento', 'descripcion', 'requisitos', 'derecho_pago', 
        'plazo_atencion', 'area', 'donde_presentar', 'base_legal', 
        'categoria', 'keywords'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Búsqueda inteligente de trámites con scoring de relevancia
     */
    public function buscarTramiteInteligente(string $busqueda): array
    {
        if (empty($busqueda)) {
            return [];
        }

        // 1. Limpieza rigurosa de la consulta para aislar el nombre del trámite
        $busquedaLimpia = $this->limpiarConsulta($busqueda);
        
        // 2. Preparar término y expandir consulta con sinónimos comunes
        $terminoInicial = !empty($busquedaLimpia) ? $busquedaLimpia : $busqueda;
        $terminoBusqueda = $this->expandirSinonimos($terminoInicial);

        // 2.5. NUEVO: Detectar si buscan "licencia funcionamiento" específicamente
        $busquedaLower = mb_strtolower($busqueda, 'UTF-8');
        $busquedaLimpiaLower = mb_strtolower($busquedaLimpia, 'UTF-8');
        $esLicenciaFuncionamiento = (
            strpos($busquedaLower, 'licencia') !== false && strpos($busquedaLower, 'funcionamiento') !== false
        ) || (
            strpos($busquedaLimpiaLower, 'licencia') !== false && strpos($busquedaLimpiaLower, 'funcionamiento') !== false
        );

        // 3. Búsqueda con scoring mejorado
        // MODIFICADO: Dar MUCHO más peso al nombre_procedimiento que a los requisitos
        // Así evitamos que trámites que mencionen "licencia de funcionamiento" 
        // en sus requisitos aparezcan antes que las licencias reales.
        
        $sql = "SELECT *, 
                (
                    ts_rank(to_tsvector('spanish', nombre_procedimiento), phraseto_tsquery('spanish', ?)) * 50 +
                    ts_rank(to_tsvector('spanish', nombre_procedimiento), plainto_tsquery('spanish', ?)) * 25 +
                    ts_rank(to_tsvector('spanish', COALESCE(keywords, '')), plainto_tsquery('spanish', ?)) * 20 +
                    ts_rank(to_tsvector('spanish', COALESCE(requisitos, '')), plainto_tsquery('spanish', ?)) * 0.5 +
                    (CASE WHEN LOWER(nombre_procedimiento) LIKE LOWER(?) THEN 30 ELSE 0 END) +
                    (CASE WHEN LOWER(nombre_procedimiento) = LOWER(?) THEN 100 ELSE 0 END) -
                    (CASE WHEN LOWER(nombre_procedimiento) LIKE '%duplicado%' OR LOWER(nombre_procedimiento) LIKE '%renovacion%' THEN 25 ELSE 0 END) -
                    (LENGTH(nombre_procedimiento) / 25.0)
                ) as relevancia
                FROM {$this->table}
                WHERE 
                    to_tsvector('spanish', COALESCE(nombre_procedimiento, '') || ' ' || COALESCE(keywords, '')) @@ plainto_tsquery('spanish', ?)
                    OR LOWER(nombre_procedimiento) LIKE ?
                    OR LOWER(keywords) LIKE ?
                ORDER BY relevancia DESC
                LIMIT 10";

        $likePattern = '%' . strtolower($busquedaLimpia) . '%';
        $prefixPattern = strtolower($busquedaLimpia) . '%';
        
        $results = $this->db->query($sql, [
            $terminoBusqueda, // Para phraseto_tsquery
            $terminoBusqueda, // Para plainto_tsquery
            $terminoBusqueda, // Keywords
            $terminoBusqueda, // Requisitos (peso reducido)
            $prefixPattern,   // Boost por prefijo
            $busquedaLimpia,  // Boost por coincidencia exacta
            $terminoBusqueda, // WHERE match combinada (solo nombre + keywords, NO requisitos)
            $likePattern,     // Fallback LIKE nombre
            $likePattern      // Fallback LIKE keywords
        ])->getResultArray();

        // NUEVO: Si el usuario busca específicamente "licencia funcionamiento",
        // forzar búsqueda directa por LIKE para asegurar que traigamos las licencias
        if ($esLicenciaFuncionamiento && (empty($results) || !$this->resultadosIncluyenLicencias($results))) {
            $sqlDirecto = "SELECT *, 100.0 as relevancia 
                          FROM {$this->table} 
                          WHERE LOWER(nombre_procedimiento) LIKE '%licencia%funcionamiento%'
                            AND LOWER(nombre_procedimiento) NOT LIKE '%no requieren%licencia%'
                            AND LOWER(nombre_procedimiento) NOT LIKE '%sin licencia%'
                          ORDER BY 
                            (CASE WHEN LOWER(nombre_procedimiento) LIKE '%duplicado%' OR LOWER(nombre_procedimiento) LIKE '%renovacion%' THEN 1 ELSE 0 END),
                            LENGTH(nombre_procedimiento)
                          LIMIT 10";
            $licenciasDirectas = $this->db->query($sqlDirecto)->getResultArray();
            
            if (!empty($licenciasDirectas)) {
                // Combinar y reordenar
                $results = $this->combinarResultados($licenciasDirectas, $results);
            }
        }

        // 4. NUEVO: Fallback de búsqueda laxa (OR) si no hay resultados estrictos
        // Esto ayuda cuando el usuario agrega verbos o palabras que no están en el trámite (ej: "realizar")
        if (empty($results)) {
            // Convertir término a palabras separadas por OR para el tsquery
            // Ej: "pre declaratoria edificacion" -> "pre | declaratoria | edificacion"
            $palabras = explode(' ', $terminoBusqueda);
            $palabrasValidas = array_filter($palabras, function($p) { return strlen($p) > 3; });
            
            if (count($palabrasValidas) > 1) {
                $queryLaxa = implode(' | ', $palabrasValidas);
                
                $sqlLaxo = "SELECT *, 
                        (
                            ts_rank(to_tsvector('spanish', nombre_procedimiento), to_tsquery('spanish', ?)) * 20 +
                            ts_rank(to_tsvector('spanish', COALESCE(keywords, '')), to_tsquery('spanish', ?)) * 5
                        ) as relevancia
                        FROM {$this->table}
                        WHERE 
                            to_tsvector('spanish', COALESCE(nombre_procedimiento, '') || ' ' || COALESCE(keywords, '')) @@ to_tsquery('spanish', ?)
                        ORDER BY relevancia DESC
                        LIMIT 5";

                $resultadosLaxos = $this->db->query($sqlLaxo, [$queryLaxa, $queryLaxa, $queryLaxa])->getResultArray();
                
                $results = array_filter($resultadosLaxos, function($row) {
                    return $row['relevancia'] >= 0.1; 
                });
                
                // DEBUG: Log si sigue vacío
                if (empty($results)) {
                    log_message('error', 'Busqueda laxa vacia. Query: ' . $queryLaxa);
                }
                
                // Reindexar array
                $results = array_values($results);
            }
        }
        
        // 5. BOOST POR CATEGORÍA: Priorizar trámites del mismo área que el mejor resultado
        if (!empty($results)) {
            // El primer resultado es el de mayor relevancia textual
            $mejorResultado = $results[0];
            $categoriaPrincipal = $mejorResultado['categoria'] ?? null;
            
            if ($categoriaPrincipal) {
                foreach ($results as &$row) {
                    // Si el trámite pertenece a la misma categoría que el mejor match, le damos un boost extra
                    if (($row['categoria'] ?? '') === $categoriaPrincipal) {
                        $row['relevancia'] += 15.0; // Boost significativo para agrupar por área
                    }
                }
                
                // Volver a ordenar por la nueva relevancia
                usort($results, function($a, $b) {
                    return $b['relevancia'] <=> $a['relevancia'];
                });
            }
        }

        return $results;
    }

    /**
     * Elimina palabras de relleno para dejar solo los términos clave del trámite
     */
    private function limpiarConsulta(string $texto): string
    {
        // Convertir a minúsculas
        $texto = mb_strtolower($texto, 'UTF-8');
        
        // Palabras de relleno comunes en consultas conversacionales
        // Eliminamos preposiciones/artículos para que la búsqueda "plainto_tsquery" (AND) no falle
        // si el usuario incluye palabras que no están en el texto del trámite.
        $stopwords = [
            'quiero', 'necesito', 'deseo', 'busco', 'gustaria', 'quisiera',
            'saber', 'conocer', 'informacion', 'sobre', 'acerca', 'referente',
            'de', 'del', 'para', 'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
            'cuales', 'cual', 'que', 'son', 'es', 'donde', 'como', 'cuando',
            'cuanto', 'cuesta', 'requisitos', 'necesarios', 'documentos', 'papeles',
            'requerimientos', 'requerimiento', 'requisito',
            'tramite', 'procedimiento', 'pasos', 'hacer', 'realizar', 'tramitar',
            'hola', 'buenos', 'dias', 'tardes', 'noches', 'por', 'favor',
            'dime', 'decirme', 'podrias', 'ayudarme', 'necesito', 'ayuda',
            'puedes', 'puedo', 'podria', 'quisiera', 'quisiese', 'tengo', 'hay',
            'este', 'esta', 'estos', 'estas', 'esto', 'ese', 'esa', 'esos', 'esas', 'eso'
        ];

        // Reemplazar stopwords con espacios
        // Usamos \b para asegurar que sean palabras completas
        $patron = '/\b(' . implode('|', $stopwords) . ')\b/u';
        $textoLimpio = preg_replace($patron, ' ', $texto);
        
        // Limpiar espacios múltiples y caracteres extraños
        $textoLimpio = preg_replace('/\s+/', ' ', $textoLimpio);
        
        return trim($textoLimpio);
    }

    /**
     * Expande términos de búsqueda con sinónimos comunes cuando no se encuentra coincidencia exacta
     */
    private function expandirSinonimos(string $texto): string
    {
        $textoOriginal = mb_strtolower($texto, 'UTF-8');
        $texto = $textoOriginal;
        
        // Mapeo de términos coloquiales a términos técnicos TUPA
        // Para estos términos, es mejor REEMPLAZAR la intención si es muy clara,
        // ya que buscar "casarme matrimonio" puede fallar si "casarme" no existe en el índice.
        $reemplazosDirectos = [
            'casar' => 'matrimonio civil',
            'casarme' => 'matrimonio civil',
            'casamiento' => 'matrimonio civil',
            'matrimonios' => 'matrimonio civil',
            'casados' => 'matrimonio civil',
            'boda' => 'matrimonio civil',
            'divorcio' => 'separación convencional y divorcio ulterior',
            'separarse' => 'separación convencional y divorcio ulterior',
            'divorciarme' => 'separación convencional y divorcio ulterior',
        ];

        foreach ($reemplazosDirectos as $buscado => $reemplazo) {
            if (preg_match('/\b' . preg_quote($buscado, '/') . '\b/u', $textoOriginal)) {
                // Si encontramos "casarme", devolvemos DIRECTAMENTE "matrimonio civil"
                // ignorando el resto para maximizar precisión en Full Text Search
                return $reemplazo;
            }
        }

        // Sinónimos complementarios (concatenación)
        // Estos se agregan para enriquecer, no para reemplazar
        $sinonimos = [
            'partida' => 'acta copia certificada',
            'nacimiento' => 'acta copia certificada',
            'defuncion' => 'acta copia certificada',
            'fallecimiento' => 'acta copia certificada',
            'reciclaje' => 'recicladores residuos sólidos',
            'reciclar' => 'recicladores residuos sólidos',
            'reciclador' => 'recicladores',
        ];

        foreach ($sinonimos as $buscado => $reemplazo) {
            if (preg_match('/\b' . preg_quote($buscado, '/') . '\b/u', $texto)) {
                $texto .= ' ' . $reemplazo;
            }
        }

        return $texto;
    }

    /**
     * Búsqueda para el panel administrativo (más flexible que LIKE simple)
     */
    public function buscarTramiteAdmin($busqueda)
    {
        if (empty($busqueda)) {
            return $this;
        }

        // Usar la lógica de limpieza para mejorar la coincidencia
        $busquedaLimpia = $this->limpiarConsulta($busqueda);
        $termino = !empty($busquedaLimpia) ? $busquedaLimpia : $busqueda;
        $likePattern = '%' . strtolower($termino) . '%';

        // Combinar búsqueda por código, nombre y palabras clave
        return $this->groupStart()
                        ->like('nombre_procedimiento', $termino)
                        ->orLike('codigo', $busqueda)
                        ->orLike('keywords', $termino)
                        ->orLike('requisitos', $termino)
                    ->groupEnd();
    }


    /**
     * Obtener todas las categorías disponibles
     */
    public function getAllCategorias(): array
    {
        return $this->select('categoria')
                    ->distinct()
                    ->where('categoria IS NOT NULL')
                    ->findAll();
    }

    /**
     * Verificar si una pregunta está relacionada con el TUPA
     */
    public function esConsultaTUPA(string $pregunta): bool
    {
        $pregunta = strtolower($pregunta);
        
        // Palabras clave que indican que NO es sobre TUPA
        $palabrasNoTupa = [
            'clima', 'tiempo', 'temperatura', 'lluvia',
            'receta', 'cocina', 'ceviche', 'platillo',
            'fútbol', 'deporte', 'partido', 'mundial',
            'música', 'canción', 'película', 'actor',
            'chiste', 'broma', 'historia', 'cuento'
        ];

        foreach ($palabrasNoTupa as $palabra) {
            if (strpos($pregunta, $palabra) !== false) {
                return false;
            }
        }

        // Palabras clave que indican que SÍ es sobre TUPA (AMPLIADAS)
        $palabrasTupa = [
            'trámite', 'tramite', 'trámites', 'tramites', 'procedimiento', 'procedimientos', 
            'requisito', 'requisitos', 'requerimiento', 'requerimientos',
            'certificado', 'certificados', 'licencia', 'licencias', 'permiso', 'permisos', 
            'autorización', 'autorizacion', 'autorizaciones',
            'municipalidad', 'municipal', 'registro', 'registros', 'civil',
            'matrimonio', 'matrimonios', 'nacimiento', 'nacimientos', 'defunción', 'defuncion', 'defunciones', 
            'construcción', 'construccion', 'construcciones',
            'casar', 'casarme', 'casamiento', 'boda', 'bodas', 'divorcio', 'divorcios', 'divorciarme', 
            'separación', 'separacion', 'separarme',
            'reciclaje', 'reciclar', 'reciclador', 'recicladores', 'residuos', 'propaganda', 'panel', 'anuncio', 'anuncios',
            'pago', 'pagos', 'costo', 'costos', 'precio', 'precios', 'tarifa', 'tarifas', 'plazo', 'plazos',
            'documento', 'documentos', 'presentar', 'solicitud', 'solicitudes', 'gestión', 'gestion',
            // NUEVAS PALABRAS PARA LENGUAJE NATURAL
            'necesito', 'necesitan', 'necesita', 'requiero', 'necesitas',
            'llevar', 'sacar', 'obtener', 'hacer', 'solicitar', 'pedir',
            'edificación', 'edificacion', 'edificaciones', 'vehiculo', 'vehículo', 'vehiculos', 'vehículos', 
            'constancia', 'constancias',
            'quiero', 'quisiera', 'gustaría', 'información', 'informacion', 'consultar', 'consulta', 'preguntar'
        ];

        foreach ($palabrasTupa as $palabra) {
            // Usar límites de palabra para evitar que "funcionar" coincida con "funcion"
            if (preg_match('/\b' . preg_quote($palabra, '/') . '\b/i', $pregunta)) {
                return true;
            }
        }

        // Si no hay palabras clave TUPA explícitas, no es una consulta válida
        return false;
    }

    /**
     * Extraer palabras clave de una consulta
     */
    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        
        // Stop words expandidos - filtra palabras comunes y errores de tipeo
        $stopWords = [
            'de', 'la', 'el', 'los', 'las', 'para', 'por', 'con', 'en', 'del', 'al', 'un', 'una', 'como', 'que', 'cual',
            'quiero', 'quero', 'quisiera', 'necesito', 'necesita', 'necesitan',
            'saber', 'conocer', 'información', 'informacion', 'sobre',
            'documentos', 'docuemntos', 'requisitos', 'requerimientos',
            'llevar', 'presentar', 'hacer', 'sacar', 'obtener'
        ];
        
        $words = preg_split('/\s+/', $text);
        $keywords = array_filter($words, function($word) use ($stopWords) {
            $word = trim($word);
            return strlen($word) >= 4 && !in_array($word, $stopWords);
        });

        return array_values($keywords);
    }

    /**
     * Obtener procedimientos por categoría
     */
    public function getPorCategoria(string $categoria): array
    {
        return $this->where('categoria', $categoria)->findAll();
    }

    /**
     * Obtener sugerencias de procedimientos populares
     */
    public function getSugerencias(): array
    {
        return $this->select('nombre_procedimiento, categoria')
                    ->orderBy('id', 'ASC')
                    ->findAll(10);
    }

    /**
     * Verificar si los resultados incluyen licencias de funcionamiento reales
     */
    private function resultadosIncluyenLicencias(array $resultados): bool
    {
        foreach ($resultados as $resultado) {
            $nombre = mb_strtolower($resultado['nombre_procedimiento'] ?? '', 'UTF-8');
            // Ser ESTRICTO: solo cuenta si el nombre COMIENZA con "licencia de funcionamiento"
            // o si es "transferencia de licencia de funcionamiento" o "duplicado de licencia"
            if (strpos($nombre, 'licencia de funcionamiento') === 0 || 
                strpos($nombre, 'transferencia de licencia de funcionamiento') === 0 ||
                strpos($nombre, 'duplicado de licencia de funcionamiento') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Combinar resultados priorizando licencias directas
     */
    private function combinarResultados(array $licencias, array $otros): array
    {
        $combinados = $licencias;
        $idsAgregados = array_column($licencias, 'id');
        
        foreach ($otros as $otro) {
            if (!in_array($otro['id'], $idsAgregados)) {
                $combinados[] = $otro;
                $idsAgregados[] = $otro['id'];
            }
        }
        
        return array_slice($combinados, 0, 10);
    }
}
