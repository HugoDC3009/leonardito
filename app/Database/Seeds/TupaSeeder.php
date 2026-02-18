<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TupaSeeder extends Seeder
{
    public function run()
    {
        // Limpiar tabla primero
        $this->db->table('tramites_tupa')->truncate();

        // Procedimientos del TUPA de la Municipalidad de José Leonardo Ortiz
        $tramites = [
            [
                'codigo' => '01',
                'nombre_procedimiento' => 'MATRIMONIO CIVIL',
                'requisitos' => '1. Copia simple del DNI de los contrayentes.
2. Copia certificada de partida de nacimiento de ambos contrayentes (con antigüedad no mayor a 3 meses).
3. Certificado médico prenupcial (con antigüedad no mayor a 30 días).
4. Declaración jurada de domicilio.
5. Declaración jurada de estado civil.
6. En caso de viudez: Copia certificada de partida de defunción del cónyuge.
7. En caso de divorcio: Copia certificada de sentencia de divorcio inscrita en RENIEC.
8. Dos testigos mayores de edad con DNI.',
                'derecho_pago' => 'S/. 82.00 (en horario normal)',
                'plazo_atencion' => '15 días hábiles para fijar fecha de matrimonio',
                'area' => 'Registro Civil',
                'donde_presentar' => 'Mesa de Partes del Registro Civil - Sede de la Municipalidad',
                'base_legal' => 'Código Civil, Ley Orgánica de Municipalidades',
                'categoria' => 'Registro Civil',
                'keywords' => 'matrimonio, civil, casamiento, boda, contrayentes, registro civil',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '02',
                'nombre_procedimiento' => 'CERTIFICADO DE NACIMIENTO',
                'requisitos' => '1. DNI del solicitante.
2. Indicar datos de la persona (nombres, apellidos, fecha de nacimiento, nombre de los padres).',
                'derecho_pago' => 'S/. 18.00 por certificado',
                'plazo_atencion' => 'Inmediato',
                'area' => 'Registro Civil',
                'donde_presentar' => 'Ventanilla de Registro Civil - Municipalidad',
                'base_legal' => 'Ley N° 26497, D.S. N° 015-98-PCM',
                'categoria' => 'Registro Civil',
                'keywords' => 'nacimiento, certificado, partida, recien nacido, bebe',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '03',
                'nombre_procedimiento' => 'CERTIFICADO DE DEFUNCIÓN',
                'requisitos' => '1. DNI del solicitante.
2. Datos del fallecido (nombres, apellidos, fecha de defunción).
3. Número de acta de defunción (si lo tiene).',
                'derecho_pago' => 'S/. 18.00 por certificado',
                'plazo_atencion' => 'Inmediato',
                'area' => 'Registro Civil',
                'donde_presentar' => 'Ventanilla de Registro Civil - Municipalidad',
                'base_legal' => 'Ley N° 26497, D.S. N° 015-98-PCM',
                'categoria' => 'Registro Civil',
                'keywords' => 'defuncion, muerte, fallecimiento, certificado, partida',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '04',
                'nombre_procedimiento' => 'LICENCIA DE FUNCIONAMIENTO',
                'requisitos' => '1. Solicitud de Licencia de Funcionamiento con carácter de Declaración Jurada (incluye TUPA).
2. Vigencia de poder del representante legal (en caso de personas jurídicas).
3. Declaración Jurada de Observancia de Condiciones de Seguridad.
4. Pago por derecho de trámite.
5. Copia del RUC.
6. Título profesional del responsable técnico (según actividad).',
                'derecho_pago' => 'Hasta 100 m²: S/. 426.00
De 100 a 500 m²: S/. 639.00
Más de 500 m²: S/. 1,065.00',
                'plazo_atencion' => '15 días hábiles',
                'area' => 'Gerencia de Desarrollo Económico',
                'donde_presentar' => 'Mesa de Partes Principal - Municipalidad',
                'base_legal' => 'Ley N° 28976, D.S. N° 046-2017-PCM',
                'categoria' => 'Licencias y Autorizaciones',
                'keywords' => 'licencia, funcionamiento, negocio, empresa, comercio, local',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '05',
                'nombre_procedimiento' => 'CERTIFICADO DE PARÁMETROS URBANÍSTICOS Y EDIFICATORIOS',
                'requisitos' => '1. Solicitud dirigida al Alcalde.
2. Recibo de pago por derecho de trámite.
3. Copia del DNI del solicitante.
4. Copia simple del título de propiedad o documento que acredite propiedad.
5. Plano de ubicación y localización (escala 1/5000).',
                'derecho_pago' => 'S/. 125.00',
                'plazo_atencion' => '10 días hábiles',
                'area' => 'Gerencia de Desarrollo Urbano',
                'donde_presentar' => 'Mesa de Partes - Sub Gerencia de Obras Privadas',
                'base_legal' => 'Ley N° 29090, D.S. N° 008-2013-VIVIENDA',
                'categoria' => 'Obras y Edificaciones',
                'keywords' => 'parametros, urbanisticos, edificatorios, construccion, zonificacion',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '06',
                'nombre_procedimiento' => 'DUPLICADO DE TARJETA DE OPERATIVIDAD DE VEHÍCULOS MENORES',
                'requisitos' => '1. Solicitud dirigida al Alcalde.
2. Copia del DNI del propietario.
3. Copia de la tarjeta de operatividad original (si la tiene).
4. Recibo de pago por derecho de trámite.
5. Declaración jurada de pérdida o deterioro.',
                'derecho_pago' => 'S/. 45.00',
                'plazo_atencion' => '7 días hábiles',
                'area' => 'Gerencia de Transporte',
                'donde_presentar' => 'Mesa de Partes - Gerencia de Transporte',
                'base_legal' => 'Ordenanza Municipal',
                'categoria' => 'Transporte',
                'keywords' => 'duplicado, tarjeta, operatividad, vehiculo, menor, mototaxi',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '07',
                'nombre_procedimiento' => 'SALIDA DE VEHÍCULO DEL DEPÓSITO MUNICIPAL',
                'requisitos' => '1. Solicitud dirigida al Alcalde.
2. DNI del propietario del vehículo.
3. Tarjeta de propiedad del vehículo.
4. Pago de multa correspondiente.
5. Pago por derecho de grúa y almacenaje.
6. Certificado de revisión técnica vigente (si aplica).',
                'derecho_pago' => 'Grúa: S/. 180.00
Almacenaje: S/. 8.00 por día',
                'plazo_atencion' => 'Inmediato (una vez cancelados los pagos)',
                'area' => 'Gerencia de Transporte',
                'donde_presentar' => 'Depósito Municipal - Gerencia de Transporte',
                'base_legal' => 'Ordenanza Municipal sobre Tránsito',
                'categoria' => 'Transporte',
                'keywords' => 'salida, vehiculo, deposito, municipal, grua, internamiento',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '08',
                'nombre_procedimiento' => 'CONSTANCIA DE POSESIÓN',
                'requisitos' => '1. Solicitud dirigida al Alcalde.
2. DNI del solicitante.
3. Recibo de pago por derecho de trámite.
4. Declaración jurada de posesión.
5. Croquis de ubicación del predio.
6. Copia de recibos de luz, agua o predio (mínimo 6 meses).',
                'derecho_pago' => 'S/. 65.00',
                'plazo_atencion' => '30 días hábiles',
                'area' => 'Gerencia de Desarrollo Urbano',
                'donde_presentar' => 'Mesa de Partes Principal',
                'base_legal' => 'Ley Orgánica de Municipalidades',
                'categoria' => 'Certificaciones',
                'keywords' => 'constancia, posesion, predio, terreno, propiedad',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '09',
                'nombre_procedimiento' => 'LICENCIA DE EDIFICACIÓN - MODALIDAD A',
                'requisitos' => '1. FUE por triplicado.
2. Declaración Jurada de Habilitación del Profesional.
3. Planos de arquitectura, estructuras, instalaciones eléctricas y sanitarias.
4. Memoria descriptiva.
5. Estudio de mecánica de suelos.
6. Pago por derecho de trámite.',
                'derecho_pago' => 'Variable según área a edificar (desde S/. 250.00)',
                'plazo_atencion' => '30 días hábiles',
                'area' => 'Sub Gerencia de Obras Privadas',
                'donde_presentar' => 'Mesa de Partes - Obras Privadas',
                'base_legal' => 'Ley N° 29090, Reglamento Nacional de Edificaciones',
                'categoria' => 'Obras y Edificaciones',
                'keywords' => 'licencia, edificacion, construccion, obra, planos',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '10',
                'nombre_procedimiento' => 'CERTIFICADO DE NUMERACIÓN MUNICIPAL',
                'requisitos' => '1. Solicitud dirigida al Alcalde.
2. DNI del propietario.
3. Copia simple del título de propiedad.
4. Croquis de ubicación.
5. Recibo de pago por derecho de trámite.',
                'derecho_pago' => 'S/. 45.00',
                'plazo_atencion' => '7 días hábiles',
                'area' => 'Gerencia de Desarrollo Urbano',
                'donde_presentar' => 'Mesa de Partes Principal',
                'base_legal' => 'Ordenanza Municipal',
                'categoria' => 'Certificaciones',
                'keywords' => 'numeracion, municipal, direccion, predio, domicilio',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            // PROCEDIMIENTOS DE EDIFICACIÓN (Agregados 2026-01-08)
            [
                'codigo' => 'PA142251DA',
                'nombre_procedimiento' => 'PRE-DECLARATORIA DE EDIFICACIÓN (para todas las Modalidades: A, B, C y D)',
                'requisitos' => "1.- La sección del FUE correspondiente al Anexo C - Pre declaratoria de Edificación debidamente suscrito y por triplicado, consignando en el rubro 5, \"Anotaciones Adicionales para Uso Múltiple\"\n2.- En caso que titular del derecho a edificar sea una persona distinta a quien inicio el procedimiento de edificación, deberá presentar:\na) Documentación que acredite que cuenta con derecho a edificar y represente al titular, en caso que el solicitante de la licencia de edificación no sea el propietario del predio.\nb) Copia del documento que acredite la declaratoria de fábrica o de edificación con sus respectivos planos en caso no haya sido expedido por la Municipalidad; en su defecto, copia del certificado de conformidad o finalización de obra, o la licencia de obra o de edificación de la construcción existente; Para los casos de remodelaciones, ampliaciones o demoliciones\nc) En caso el solicitante sea una persona jurídica, se acompañará vigencia del poder expedida por el Registro de Personas Jurídicas, con una antigüedad no mayor a treinta (30) días calendario\n3.- Indicar el número de recibo y la fecha del Pago de la tasa municipal respectiva\n4.- Copia de los Planos de Ubicación y de la especialidad de Arquitectura de la Licencia respectiva, por triplicado.\n\nNotas:\n(a)El Formulario y sus anexos deben ser visados en todas sus páginas y cuando corresponda, firmados por el propietario o por el solicitante y los profesionales que interviene.\n(b)Cuando se trate de edificaciones en las que coexistan unidades inmobiliarias de propiedad exclusiva y de propiedad común y bienes y/o servicios comunes, se inscriben necesariamente en un mismo acto la predeclaratoria de edificación, la preindependización y el pre reglamento interno respectivo.",
                'derecho_pago' => 'S/ 663.60',
                'plazo_atencion' => '5 días hábiles (Aprobación automática)',
                'area' => 'SUBGERENCIA DE HABILITACIONES URBANAS Y EDIFICACIONES PRIVADAS',
                'donde_presentar' => 'Mesa de Partes Principal',
                'base_legal' => 'Ley 29090',
                'categoria' => 'Licencias y Autorizaciones',
                'keywords' => 'pre-declaratoria edificación declaratoria fabrica',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '12',
                'nombre_procedimiento' => 'VISACIÓN DE PLANOS',
                'requisitos' => '1. Planos arquitectónicos.
2. Habilitación profesional.
3. Memoria descriptiva.
4. Pago de derecho.',
                'derecho_pago' => 'S/. 100.00',
                'plazo_atencion' => '10 días hábiles',
                'area' => 'Obras Privadas',
                'donde_presentar' => 'Mesa de Partes Obras',
                'base_legal' => 'Ley 29090',
                'categoria' => 'Obras',
                'keywords' => 'visación, planos, arquitectura, aprobación',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '13',
                'nombre_procedimiento' => 'SUBDIVISIÓN DE LOTE',
                'requisitos' => '1. Solicitud.
2. Planos de subdivisión.
3. Título de propiedad.
4. Certificado de parámetros.
5. Pago de derecho.',
                'derecho_pago' => 'S/. 200.00',
                'plazo_atencion' => '30 días hábiles',
                'area' => 'Desarrollo Urbano',
                'donde_presentar' => 'Mesa de Partes',
                'base_legal' => 'Ley 29090',
                'categoria' => 'Obras',
                'keywords' => 'subdivisión, lote, división, predio',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '14',
                'nombre_procedimiento' => 'INDEPENDIZACIÓN DE PREDIO',
                'requisitos' => '1. Solicitud.
2. Planos de independización.
3. Título de propiedad.
4. Declaración de fábrica.
5. Pago de derecho.',
                'derecho_pago' => 'S/. 250.00',
                'plazo_atencion' => '30 días hábiles',
                'area' => 'Desarrollo Urbano',
                'donde_presentar' => 'Mesa de Partes',
                'base_legal' => 'Ley 29090',
                'categoria' => 'Obras',
                'keywords' => 'independización, predio, separación, independizar',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '15',
                'nombre_procedimiento' => 'RECEPCIÓN DE OBRA',
                'requisitos' => '1. Licencia de edificación.
2. Planos de conformidad.
3. Declaración jurada de finalización de obra.
4. Pago de derecho.',
                'derecho_pago' => 'S/. 180.00',
                'plazo_atencion' => '15 días hábiles',
                'area' => 'Obras Privadas',
                'donde_presentar' => 'Mesa de Partes Obras',
                'base_legal' => 'Ley 29090',
                'categoria' => 'Obras',
                'keywords' => 'recepción, obra, finalización, término',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'codigo' => '16',
                'nombre_procedimiento' => 'INSPECCIÓN OBRA',
                'requisitos' => '1. Solicitud de inspección.
2. Licencia de edificación.
3. Cronograma de avance.',
                'derecho_pago' => 'S/. 120.00',
                'plazo_atencion' => '7 días hábiles',
                'area' => 'Obras Privadas',
                'donde_presentar' => 'Mesa de Partes Obras',
                'base_legal' => 'Ley 29090',
                'categoria' => 'Obras',
                'keywords' => 'inspección, obra, verificación, supervisión',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Insertar todos los trámites
        foreach ($tramites as $tramite) {
            $this->db->table('tramites_tupa')->insert($tramite);
        }

        echo "✅ Se insertaron " . count($tramites) . " procedimientos del TUPA correctamente.\n";
    }
}
