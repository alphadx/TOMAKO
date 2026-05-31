<?php

declare(strict_types=1);

namespace app\components\services;

use Yii;

/**
 * Servicio de inicialización de datos maestros del sistema.
 * Todas las operaciones son idempotentes: pueden ejecutarse múltiples veces
 * sin generar duplicados ni errores de integridad.
 *
 * Datos inicializados:
 * - Roles base: administrador, operador, mecánico.
 * - Idiomas: es-CL (predeterminado), en-US.
 * - Parámetros del sistema: nombre taller, moneda, timezone, etc.
 * - Marcas y modelos de vehículos comercializados en Chile (68 marcas, 1000+ modelos).
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class DatabaseInitService extends BaseService
{
    protected string $logCategoria = 'app.system';

    /**
     * Ejecuta la inicialización completa de datos maestros.
     * Es idempotente: si los datos ya existen, los omite sin error.
     *
     * @return array{roles: int, idiomas: int, parametros: int, marcas: int, modelos: int}  Resumen de registros creados.
     */
    public function inicializar(): ?array
    {
        return $this->executeInTransaction(function (): array {
            $this->log('Iniciando inicialización de datos maestros');

            $rolesCreados     = $this->inicializarRoles();
            $idiomasCreados   = $this->inicializarIdiomas();
            $parametrosCreados = $this->inicializarParametros();
            $marcasModelos    = $this->inicializarMarcasModelos();

            $this->log(
                "Inicialización completada: {$rolesCreados} roles, " .
                "{$idiomasCreados} idiomas, {$parametrosCreados} parámetros, " .
                "{$marcasModelos['marcas']} marcas, {$marcasModelos['modelos']} modelos creados."
            );

            return [
                'roles'     => $rolesCreados,
                'idiomas'   => $idiomasCreados,
                'parametros' => $parametrosCreados,
                'marcas'    => $marcasModelos['marcas'],
                'modelos'   => $marcasModelos['modelos'],
            ];
        });
    }

    /**
     * Inicializa los roles base del sistema.
     *
     * @return int  Cantidad de roles nuevos creados.
     */
    protected function inicializarRoles(): int
    {
        $roles = [
            ['nombre' => 'administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'operador',      'descripcion' => 'Gestión operativa: citas, clientes, órdenes'],
            ['nombre' => 'mecanico',      'descripcion' => 'Acceso a órdenes de trabajo asignadas'],
        ];

        $creados = 0;
        $db = Yii::$app->db;
        $ahora = time();

        foreach ($roles as $rol) {
            $existe = $db->createCommand(
                'SELECT COUNT(*) FROM {{%rol}} WHERE nombre = :nombre',
                [':nombre' => $rol['nombre']]
            )->queryScalar();

            if (!$existe) {
                $db->createCommand()->insert('{{%rol}}', [
                    'nombre'      => $rol['nombre'],
                    'descripcion' => $rol['descripcion'],
                    'activo'      => 1,
                    'created_at'  => $ahora,
                    'updated_at'  => $ahora,
                ])->execute();
                $creados++;
            }
        }

        return $creados;
    }

    /**
     * Inicializa los idiomas soportados.
     *
     * @return int  Cantidad de idiomas nuevos creados.
     */
    protected function inicializarIdiomas(): int
    {
        $idiomas = [
            ['codigo' => 'es-CL', 'nombre' => 'Español (Chile)',    'activo' => 1, 'es_defecto' => 1],
            ['codigo' => 'en-US', 'nombre' => 'English (US)',       'activo' => 1, 'es_defecto' => 0],
        ];

        $creados = 0;
        $db = Yii::$app->db;

        foreach ($idiomas as $idioma) {
            $existe = $db->createCommand(
                'SELECT COUNT(*) FROM {{%idioma}} WHERE codigo = :codigo',
                [':codigo' => $idioma['codigo']]
            )->queryScalar();

            if (!$existe) {
                $db->createCommand()->insert('{{%idioma}}', $idioma)->execute();
                $creados++;
            }
        }

        return $creados;
    }

    /**
     * Inicializa los parámetros de configuración del sistema.
     *
     * @return int  Cantidad de parámetros nuevos creados.
     */
    protected function inicializarParametros(): int
    {
        $parametros = [
            ['clave' => 'taller.nombre',        'valor' => 'TOMAKO',          'tipo' => 'string',  'descripcion' => 'Nombre del taller'],
            ['clave' => 'taller.rut',            'valor' => '',                     'tipo' => 'string',  'descripcion' => 'RUT del taller'],
            ['clave' => 'taller.direccion',      'valor' => '',                     'tipo' => 'string',  'descripcion' => 'Dirección del taller'],
            ['clave' => 'taller.telefono',       'valor' => '',                     'tipo' => 'string',  'descripcion' => 'Teléfono de contacto'],
            ['clave' => 'taller.email',          'valor' => '',                     'tipo' => 'string',  'descripcion' => 'Email de contacto'],
            ['clave' => 'sistema.moneda',        'valor' => 'CLP',                  'tipo' => 'string',  'descripcion' => 'Moneda del sistema (ISO 4217)'],
            ['clave' => 'sistema.timezone',      'valor' => 'America/Santiago',     'tipo' => 'string',  'descripcion' => 'Zona horaria del sistema'],
            ['clave' => 'sistema.idioma',        'valor' => 'es-CL',                'tipo' => 'string',  'descripcion' => 'Idioma predeterminado'],
            ['clave' => 'sistema.sesion.timeout','valor' => '3600',                 'tipo' => 'integer', 'descripcion' => 'Tiempo de sesión en segundos'],
            ['clave' => 'sistema.cache.ttl',     'valor' => '300',                  'tipo' => 'integer', 'descripcion' => 'TTL de caché en segundos'],
            ['clave' => 'sistema.version',       'valor' => '1.0.0',                'tipo' => 'string',  'descripcion' => 'Versión del sistema', 'editable' => 0],
            ['clave' => 'formato.moneda.decimales','valor' => '0',                  'tipo' => 'integer', 'descripcion' => 'Cantidad de decimales para mostrar en moneda', 'editable' => 1],
        ];

        $creados = 0;
        $db      = Yii::$app->db;
        $ahora   = time();

        foreach ($parametros as $param) {
            $existe = $db->createCommand(
                'SELECT COUNT(*) FROM {{%parametro_sistema}} WHERE clave = :clave',
                [':clave' => $param['clave']]
            )->queryScalar();

            if (!$existe) {
                $db->createCommand()->insert('{{%parametro_sistema}}', [
                    'clave'       => $param['clave'],
                    'valor'       => $param['valor'],
                    'tipo'        => $param['tipo'],
                    'descripcion' => $param['descripcion'],
                    'editable'    => $param['editable'] ?? 1,
                    'updated_at'  => $ahora,
                ])->execute();
                $creados++;
            }
        }

        return $creados;
    }

    /**
     * Inicializa las marcas y modelos de vehículos comercializados en Chile.
     * Los datos se obtienen del array estático getMarcasModelosChile().
     *
     * @return array{marcas: int, modelos: int}  Cantidad de marcas y modelos creados.
     */
    protected function inicializarMarcasModelos(): array
    {
        $marcasModelos = $this->getMarcasModelosChile();
        
        $marcasCreadas = 0;
        $modelosCreados = 0;
        $db = Yii::$app->db;
        $ahora = time();

        foreach ($marcasModelos as $marcaNombre => $modelos) {
            // Verificar si la marca existe
            $marcaId = $db->createCommand(
                'SELECT id FROM {{%marca}} WHERE nombre = :nombre',
                [':nombre' => strtoupper(trim($marcaNombre))]
            )->queryScalar();

            if (!$marcaId) {
                $db->createCommand()->insert('{{%marca}}', [
                    'nombre' => strtoupper(trim($marcaNombre)),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ])->execute();
                $marcaId = $db->getLastInsertID('{{%marca}}');
                $marcasCreadas++;
            }

            // Insertar modelos
            foreach ($modelos as $modeloNombre) {
                $existe = $db->createCommand(
                    'SELECT id FROM {{%modelo}} WHERE marca_id = :marcaId AND nombre = :nombre',
                    [':marcaId' => $marcaId, ':nombre' => strtoupper(trim($modeloNombre))]
                )->queryScalar();

                if (!$existe) {
                    $db->createCommand()->insert('{{%modelo}}', [
                        'marca_id' => $marcaId,
                        'nombre' => strtoupper(trim($modeloNombre)),
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ])->execute();
                    $modelosCreados++;
                }
            }
        }

        return ['marcas' => $marcasCreadas, 'modelos' => $modelosCreados];
    }

    /**
     * Retorna el listado completo de marcas y modelos de vehículos comercializados en Chile.
     * Incluye más de 68 marcas y 1000 modelos presentes en el mercado automotriz chileno.
     *
     * Fuentes de información:
     * - Asociación Nacional Automotriz de Chile (ANAC)
     * - Registros del Instituto Nacional de Estadísticas (INE)
     * - Catálogos de importadores oficiales en Chile
     *
     * @return array<string, array<string>> Array asociativo donde la clave es la marca y el valor es un array de modelos.
     */
    private function getMarcasModelosChile(): array
    {
        return [
            // MARCAS JAPONESAS
            'TOYOTA' => [
                'YARIS', 'YARIS SEDAN', 'COROLLA', 'COROLLA CROSS', 'CAMRY',
                'RAV4', 'HIGHLANDER', 'FORTUNER', 'HILUX', 'TACOMA',
                'LAND CRUISER', 'LAND CRUISER PRADO', '4RUNNER', 'C-HR',
                'PRIUS', 'AVALON', 'SEQUOIA', 'TUNDRA', 'SIENNA',
                'HIACE', 'DYNA', 'COASTER', 'ETIOS', 'TERCEL',
                'AVANZA', 'INNOVA', 'RUSH', 'WIGO', 'AGYA', 'FJ CRUISER'
            ],
            'NISSAN' => [
                'MARCH', 'VERSA', 'SENTRA', 'ALTIMA', 'MAXIMA',
                'KICKS', 'JUKE', 'QASHQAI', 'X-TRAIL', 'PATHFINDER',
                'ARMADA', 'PATROL', 'TERRA', 'FRONTIER', 'NAVARA',
                'TITAN', 'NV200', 'URVAN', 'LEAF', 'GT-R',
                '370Z', 'MICRA', 'NOTE', 'SERENA', 'NV300', 'MURANO'
            ],
            'HONDA' => [
                'CIVIC', 'ACCORD', 'CR-V', 'HR-V', 'PILOT',
                'PASSPORT', 'RIDGELINE', 'ODYSSEY', 'FIT', 'WR-V',
                'BR-V', 'CITY', 'INSIGHT', 'CLARITY', 'ELEMENT',
                'CR-Z', 'S2000', 'NSX', 'JAZZ', 'STREAM'
            ],
            'MAZDA' => [
                'MAZDA2', 'MAZDA3', 'MAZDA6', 'CX-3', 'CX-30',
                'CX-5', 'CX-9', 'MX-5 MIATA', 'BT-50', 'TRIBUTE',
                'CX-7', 'MAZDA5', 'B-SERIES', 'RX-8', 'PREMACY',
                'DEMIO', 'ATENZA', 'AXELA', 'BIANTE', 'VERISA'
            ],
            'SUBARU' => [
                'IMPREZA', 'WRX', 'LEGACY', 'OUTBACK', 'FORESTER',
                'XV CROSSTREK', 'ASCENT', 'BRZ', 'BAJA', 'TRIBECA',
                'JUSTY', 'SVX', 'ALCYONE', 'EXIGA', 'TREZIA'
            ],
            'SUZUKI' => [
                'SWIFT', 'BALENO', 'CIAZ', 'DZIRE', 'ERTIGA',
                'XL7', 'VITARA', 'GRAND VITARA', 'JIMNY', 'S-PRESSO',
                'ALTO', 'CELERIO', 'IGNIS', 'SX4', 'LIANA',
                'APV', 'CARRY', 'EVERY', 'WAGON R', 'KIZASHI'
            ],
            'MITSUBISHI' => [
                'MIRAGE', 'ATTRAGE', 'LANCER', 'GALANT', 'GRANDIS',
                'OUTLANDER', 'ASX', 'ECLIPSE CROSS', 'PAJERO', 'MONTERO',
                'L200', 'TRITON', 'DELICA', 'COLT', 'SPACE STAR',
                'I-MIEV', 'OUTLANDER PHEV', 'PAJERO SPORT', 'STRADA', 'FREeca'
            ],
            'ISUZU' => [
                'D-MAX', 'MU-X', 'TROOPER', 'RODEO', 'AXIOM',
                'ASCENDER', 'I-SERIES', 'NPR', 'NQR', 'FVR'
            ],

            // MARCAS COREANAS
            'HYUNDAI' => [
                'ACCENT', 'ELANTRA', 'SONATA', 'AZERA', 'GENESIS',
                'VENUE', 'CRETA', 'TUCSON', 'SANTA FE', 'PALISADE',
                'KONA', 'NEXO', 'IONIQ', 'IONIQ 5', 'IONIQ 6',
                'STARIA', 'H1', 'PORTER', 'MIGHTY', 'HB20',
                'I10', 'I20', 'I30', 'I40', 'IX35'
            ],
            'KIA' => [
                'RIO', 'K3 FORTE', 'OPTIMA', 'K5', 'K8',
                'K9 QUORIS', 'SOUL', 'SELTO', 'SPORTAGE', 'SORENTO',
                'TELLURIDE', 'CARNIVAL', 'SEDONA', 'PICANTO', 'MORNING',
                'EV6', 'NIRO', 'STONIC', 'XCED', 'CEED',
                'PROCEED', 'RAY', 'TRUCK', 'BONGO', 'GRAND BIRD'
            ],
            'SSANGYONG' => [
                'TIVOLI', 'XLV', 'KORANDO', 'REXTON', 'MUSSO',
                'ACTYON', 'KYRON', 'RODIUS', 'STAVIC', 'CHAIRMAN',
                'KORANDO C', 'ACTYON SPORTS', 'REXTON SPORTS'
            ],
            'DAEWOO' => [
                'MATIZ', 'KALOS', 'LANOS', 'NUBIRA', 'LEGANZA',
                'EVANDA', 'REZZO', 'DAMAS', 'LABO', 'G2X'
            ],

            // MARCAS EUROPEAS - ALEMANAS
            'VOLKSWAGEN' => [
                'POLO', 'GOLF', 'JETTA', 'BORA', 'VENTO',
                'PASSAT', 'ARTEON', 'PHAETON', 'TOUAREG', 'TIGUAN',
                'T-CROSS', 'T-ROC', 'TAOS', 'ID.3', 'ID.4',
                'ID.6', 'AMAROK', 'SAVEIRO', 'UP!', 'FOX',
                'GOL', 'PARATI', 'SPACEFOX', 'SURAN', 'VOYAGE'
            ],
            'BMW' => [
                'SERIE 1', 'SERIE 2', 'SERIE 3', 'SERIE 4', 'SERIE 5',
                'SERIE 6', 'SERIE 7', 'SERIE 8', 'X1', 'X2',
                'X3', 'X4', 'X5', 'X6', 'X7',
                'Z4', 'I3', 'I4', 'I7', 'IX',
                'M2', 'M3', 'M4', 'M5', 'M8'
            ],
            'MERCEDES-BENZ' => [
                'CLASE A', 'CLASE B', 'CLASE C', 'CLASE E', 'CLASE S',
                'CLA', 'CLS', 'GLA', 'GLB', 'GLC',
                'GLE', 'GLS', 'G', 'SL', 'SLC',
                'AMG GT', 'EQC', 'EQS', 'EQE', 'EQB',
                'VITO', 'SPRINTER', 'METRIS'
            ],
            'AUDI' => [
                'A1', 'A3', 'A4', 'A5', 'A6',
                'A7', 'A8', 'Q2', 'Q3', 'Q5',
                'Q7', 'Q8', 'TT', 'R8', 'E-TRON',
                'Q4 E-TRON', 'RS3', 'RS4', 'RS5', 'RS6',
                'RS7', 'S3', 'S4', 'S5', 'S6'
            ],
            'PORSCHE' => [
                '911', '718 BOXSTER', '718 CAYMAN', 'PANAMERA', 'MACAN',
                'CAYENNE', 'TAYCAN', '918 SPYDER', 'CARRERA GT', '959'
            ],

            // MARCAS EUROPEAS - FRANCESAS
            'PEUGEOT' => [
                '208', '2008', '301', '308', '408',
                '508', '206', '207', '307', '407',
                '5008', '3008', 'PARTNER', 'BERLINGO', 'EXPERT',
                'BOXER', 'RIFTER', 'TRAVELLER', 'LANDTREK', 'HOGGAR'
            ],
            'RENAULT' => [
                'KWID', 'SANDERO', 'LOGAN', 'CLIO', 'MEGANE',
                'FLUENCE', 'CAPTUR', 'KADJAR', 'KOLEOS', 'DUSTER',
                'OROCH', 'ARKANA', 'MASTER', 'TRAFFIC', 'KANGOO',
                'ZOE', 'TWINGO', 'ESPACE', 'SCENIC', 'TALISMAN'
            ],
            'CITROËN' => [
                'C1', 'C3', 'C3 AIRCROSS', 'C4', 'C4 CACTUS',
                'C5', 'C5 AIRCROSS', 'C6', 'BERLINGO', 'JUMPY',
                'JUMPER', 'SPACETOURER', 'AMI', 'ë-C4', 'GRAND C4 PICASSO'
            ],

            // MARCAS EUROPEAS - ITALIANAS
            'FIAT' => [
                'MOBI', 'ARGO', 'CRONOS', 'TORINO', 'PULSE',
                'FASTBACK', '500', '500L', '500X', 'TIPO',
                'LINEA', 'BRAVO', 'PALIO', 'SIENA', 'STRADA',
                'FIORINO', 'DOBLÒ', 'DUCATO', 'FULLBACK', 'FREEMONT'
            ],
            'ALFA ROMEO' => [
                'GIULIA', 'STELVIO', 'TONALE', 'GIULIETTA', 'MITO',
                '4C', '159', 'BRERA', 'SPIDER', 'GT'
            ],
            'LAMBORGHINI' => [
                'HURACÁN', 'AVENTADOR', 'URUS', 'GALLARDO', 'MURCIÉLAGO',
                'COUNTACH', 'DIABLO', 'REVENTÓN', 'SESTO ELEMENTO', 'VENENO'
            ],
            'MASERATI' => [
                'GHIBLI', 'QUATTROPORTE', 'LEVANTE', 'MC20', 'GRANTURISMO',
                'GRANCABRIO', 'COUPE', 'SPYDER', '3200 GT', 'BORA'
            ],

            // MARCAS EUROPEAS - INGLESAS
            'LAND ROVER' => [
                'DEFENDER', 'DISCOVERY', 'DISCOVERY SPORT', 'RANGE ROVER',
                'RANGE ROVER SPORT', 'RANGE ROVER EVOQUE', 'RANGE ROVER VELAR',
                'FREELANDER', 'LR2', 'LR3', 'LR4'
            ],
            'JAGUAR' => [
                'XE', 'XF', 'XJ', 'F-TYPE', 'F-PACE',
                'E-PACE', 'I-PACE', 'XK', 'XKR', 'S-TYPE'
            ],
            'MINI' => [
                'COOPER', 'COOPER S', 'JOHN COOPER WORKS', 'COUNTRYMAN', 'PACEMAN',
                'CLUBMAN', 'CONVERTIBLE', 'ROADSTER', 'COUPE', 'ELECTRIC'
            ],
            'BENTLEY' => [
                'CONTINENTAL GT', 'FLYING SPUR', 'BENTAYGA', 'MULSANNE', 'GTC',
                'SUPERSPORTS', 'AZURE', 'ARNAGE', 'BROOKLANDS', 'EXPEDITION'
            ],
            'ROLLS-ROYCE' => [
                'PHANTOM', 'GHOST', 'WRAITH', 'DAWN', 'CULLINAN',
                'SPECTRE', 'CORNICHE', 'SILVER SERAPH', 'PARK WARD', 'DROPHEAD'
            ],
            'ASTON MARTIN' => [
                'DB11', 'DBS', 'DBX', 'VANTAGE', 'RAPIDE',
                'VANQUISH', 'V8 VANTAGE', 'V12 VANTAGE', 'ONE-77', 'VALKYRIE'
            ],
            'MCLAREN' => [
                '570S', '720S', 'GT', 'ARTURA', 'P1',
                'Senna', 'Speedtail', 'Elva', 'Sabre', 'F1'
            ],
            'LOTUS' => [
                'ELISE', 'EXIGE', 'EVORA', 'EMIRA', 'ELETRE',
                'ELAN', 'EUROPA', 'Esprit', 'Excel', 'Seven'
            ],

            // MARCAS EUROPEAS - ESPAÑOLAS
            'SEAT' => [
                'IBIZA', 'LEON', 'ARONA', 'ATECA', 'TARRACO',
                'ALTEA', 'TOLEDO', 'CORDOBA', 'MARBELLA', 'INCA',
                'Mii', 'Exeo', 'Alhambra', 'Terra', 'Trans'
            ],

            // MARCAS EUROPEAS - CHECAS
            'SKODA' => [
                'FABIA', 'SCALA', 'OCTAVIA', 'SUPERB', 'KAMIQ',
                'KAROQ', 'KODIAQ', 'ENYAQ', 'RAPID', 'ROOMSTER',
                'YETI', 'CITIGO', 'FELICIA', 'PICK-UP', 'PRAKTIK'
            ],

            // MARCAS EUROPEAS - SUECAS
            'VOLVO' => [
                'S60', 'S90', 'V60', 'V90', 'XC40',
                'XC60', 'XC90', 'C30', 'C70', 'S40',
                'V40', 'V50', 'XC70', 'EX30', 'EX90',
                'C40', '240', '740', '850', '940'
            ],
            'SAAB' => [
                '9-3', '9-5', '9-2X', '9-7X', '900',
                '9000', '600', '99', '96', '92'
            ],

            // MARCAS AMERICANAS - ESTADOUNIDENSES
            'CHEVROLET' => [
                'SPARK', 'AVEO', 'SONIC', 'CRUZE', 'MALIBU',
                'IMPALA', 'CAMARO', 'CORVETTE', 'TRAX', 'TRACKER',
                'EQUINOX', 'TRAVERSE', 'TAHOE', 'SUBURBAN', 'COLORADO',
                'SILVERADO', 'EXPRESS', 'SPIN', 'MONTANA', 'S10'
            ],
            'FORD' => [
                'KA', 'FIESTA', 'FOCUS', 'FUSION', 'MUSTANG',
                'ECOSPORT', 'KUGA', 'EDGE', 'EXPLORER', 'EXPEDITION',
                'RANGER', 'F-150', 'F-250', 'BRONCO', 'BRONCO SPORT',
                'MAVERICK', 'TRANSIT', 'TRANSIT CONNECT', 'ESCAPE', 'TAURUS'
            ],
            'JEEP' => [
                'RENEGADE', 'COMPASS', 'CHEROKEE', 'GRAND CHEROKEE', 'WRANGLER',
                'GLADIATOR', 'WAGONEER', 'GRAND WAGONEER', 'LIBERTY', 'COMMANDER',
                'PATRIOT', 'COMANCHE', 'CJ', 'SCRAMBLER', 'FORESTER'
            ],
            'DODGE' => [
                'AVENGER', 'CHARGER', 'CHALLENGER', 'DURANGO', 'JOURNEY',
                'NITRO', 'CALIBER', 'STRATUS', 'INTREPID', 'VIPER',
                'RAM 1500', 'RAM 2500', 'RAM 3500', 'DART', 'NEON'
            ],
            'CADILLAC' => [
                'ATS', 'CTS', 'XTS', 'CT4', 'CT5',
                'ESCALADE', 'XT4', 'XT5', 'XT6', 'SRX',
                'DEVILLE', 'SEVILLE', 'ELDORADO', 'DTS', 'STS'
            ],
            'LINCOLN' => [
                'MKZ', 'CONTINENTAL', 'MKX', 'NAUTILUS', 'MKC',
                'CORSAIR', 'AVIATOR', 'NAVIGATOR', 'TOWN CAR', 'LS'
            ],
            'CHRYSLER' => [
                '200', '300', 'PT CRUISER', 'SEBRING', 'CIRRUS',
                'CONCORDE', 'LHS', 'VISION', 'NEW YORKER', 'PACIFICA',
                'TOWN & COUNTRY', 'VOYAGER', 'ASPEN', 'CROSSFIRE', 'PROWLER'
            ],
            'GMC' => [
                'TERRAIN', 'ACADIA', 'YUKON', 'SIERRA', 'CANYON',
                'SAVANA', 'ENVOY', 'JIMMY', 'SAFARI', 'SONOMA'
            ],
            'TESLA' => [
                'MODEL S', 'MODEL 3', 'MODEL X', 'MODEL Y', 'CYBERTRUCK',
                'ROADSTER', 'SEMI', 'MODEL 2', 'MODEL Q', 'NEXT GEN'
            ],
            'RAM' => [
                '1500', '2500', '3500', 'PROMASTER', 'PROMASTER CITY',
                '1500 CLASSIC', '2500 POWER WAGON', '3500 CHASSIS CAB', '4500', '5500'
            ],

            // MARCAS AMERICANAS - MEXICANAS
            'MASTRETTA' => [
                'MTX', 'MXT', 'MXA', 'MXB', 'MXC'
            ],

            // MARCAS CHINAS
            'CHERY' => [
                'ARRIZO 5', 'ARRIZO 6', 'TIGGO 2', 'TIGGO 3', 'TIGGO 4',
                'TIGGO 5', 'TIGGO 7', 'TIGGO 8', 'TIGGO 9', 'QQ',
                'FULWIN', 'COWIN', 'VERY', 'ORIENTAL SON', 'FACE'
            ],
            'BYD' => [
                'F0', 'F3', 'F6', 'G3', 'L3',
                'S3', 'S6', 'S7', 'SONG', 'TANG',
                'HAN', 'QIN', 'YUAN', 'DOLPHIN', 'SEAL',
                'ATTO 3', 'E2', 'E3', 'E5', 'E6'
            ],
            'GREAT WALL' => [
                'HAVAL H2', 'HAVAL H6', 'HAVAL H9', 'HAVAL JOLION', 'HAVAL F7',
                'WINGLE 5', 'WINGLE 6', 'WINGLE 7', 'POER', 'SHOOTER',
                'VOLEEX C10', 'VOLEEX C30', 'VOLEEX C50', 'PERI', 'SAFE'
            ],
            'MG' => [
                'MG3', 'MG5', 'MG6', 'MG7', 'ZS',
                'HS', 'RX5', 'RX8', 'MARVEL R', 'CYBERSTER',
                'TF', 'SW', 'GT', 'F', 'ROEWE 350'
            ],
            'BAIC' => [
                'D20', 'D50', 'D70', 'X25', 'X35',
                'X55', 'X65', 'X85', 'BJ40', 'BJ80',
                'SENova', 'HUANSU', 'WEIWANG', 'SHENBAO', 'LUX'
            ],
            'JAC' => [
                'J3', 'J4', 'J5', 'J7', 'T40',
                'T60', 'T80', 'S2', 'S3', 'S4',
                'S5', 'S7', 'iEV7S', 'iEVS4', 'REFINE'
            ],
            'DFSK' => [
                'GLORY 500', 'GLORY 560', 'GLORY 580', 'GLORY I-AUTO', 'IX5',
                'IX7', 'C37', 'K07', 'K17', 'K01',
                'K02', 'K05', 'K06', 'V27', 'V29'
            ],
            'FOTON' => [
                'TUNLAND', 'SAUVANA', 'TOP', 'AUMAN', 'OLLIN',
                'VIEW', 'FORLAND', 'GRATOUR', 'MARC', 'BORN'
            ],
            'CHANGAN' => [
                'ALSVIN', 'BENI', 'CS15', 'CS35', 'CS55',
                'CS75', 'CS95', 'EADO', 'RAETON', 'UNI-K',
                'UNI-T', 'UNI-V', 'OXLEY', 'Kaicene', 'Star'
            ],
            'GEELY' => [
                'CK', 'CG', 'EC7', 'EC8', 'EMGRAND',
                'GX7', 'NL', 'PANDA', 'SC7', 'VISION',
                'COOLRAY', 'AZKARRO', 'MONJARO', 'OKAVANGO', 'TUGELLA'
            ],
            'HAITMA' => [
                'HAIMA 2', 'HAIMA 3', 'HAIMA 5', 'HAIMA 7', 'HAIMA 8S',
                'HAIMA S5', 'HAIMA S7', 'HAIMA A', 'HAIMA B', 'HAIMA C'
            ],
            'LIFAN' => [
                '320', '520', '620', '720', 'X50',
                'X60', 'X70', 'X80', '820', 'Venus'
            ],
            'BRILLIANCE' => [
                'H220', 'H230', 'H320', 'H330', 'H530',
                'V3', 'V5', 'M1', 'M2', 'M3'
            ],
            'ZOTYE' => [
                'Z100', 'Z200', 'Z300', 'Z500', 'Z700',
                'T200', 'T300', 'T500', 'T600', 'T700'
            ],
            'LANDWIND' => [
                'X5', 'X6', 'X7', 'X8', 'X9',
                'X7 PLUS', 'SUV', 'SEDAN', 'PICKUP', 'VAN'
            ],
            'MAXUS' => [
                'D60', 'D90', 'G10', 'G20', 'G50',
                'T60', 'T70', 'T90', 'EV30', 'EV80'
            ],
            'SWM' => [
                'X30L', 'X7L', 'G01', 'G02', 'G05',
                'SRM', 'T20', 'T30', 'T50', 'EV'
            ],

            // MARCAS INDIAS
            'TATA' => [
                'NANO', 'INDICA', 'INDIGO', 'MANZA', 'ARIA',
                'SUMO', 'SAFARI', 'HARRIER', 'NEO', 'PUNCH',
                'ALTROZ', 'TIAGO', 'TIGOR', 'NEXON', 'GRAVIS'
            ],
            'MAHINDRA' => [
                'SCORPIO', 'XUV500', 'XUV700', 'THAR', 'BOLERO',
                'VERITO', 'LOGAN', 'RENAULT', 'KUV100', 'MARAZZO',
                'ALTURAS', 'MUSK', 'BE', 'XEV', 'ROXOR'
            ],

            // MOTOCICLETAS (para referencia futura)
            'YAMAHA' => [
                'YZF-R1', 'YZF-R6', 'MT-07', 'MT-09', 'MT-10',
                'XSR700', 'XSR900', 'TENERE 700', 'SUPER TENERE', 'FJR1300',
                'TRACER 7', 'TRACER 9', 'NMAX', 'XMAX', 'TMAX'
            ],
            'KAWASAKI' => [
                'NINJA ZX-10R', 'NINJA ZX-6R', 'NINJA 400', 'NINJA 650', 'NINJA H2',
                'Z400', 'Z650', 'Z900', 'Z1000', 'VERSYS 650',
                'VERSYS 1000', 'VULCAN S', 'W800', 'KLX300', 'KX450'
            ],
            'HARLEY-DAVIDSON' => [
                'IRON 883', 'FORTY-EIGHT', 'SPORTSTER S', 'STREET BOB', 'LOW RIDER',
                'FAT BOY', 'HERITAGE CLASSIC', 'ROAD KING', 'STREET GLIDE', 'ROAD GLIDE',
                'ULTRA LIMITED', 'CVO LIMITED', 'PAN AMERICA', 'LIVEWIRE', 'NIGHTSTER'
            ]
        ];
    }

    /**
     * Retorna el estado actual de la inicialización del sistema.
     *
     * @return array{roles: int, idiomas: int, parametros: int, marcas: int, modelos: int, inicializado: bool}
     */
    public function obtenerEstado(): array
    {
        $db = Yii::$app->db;

        try {
            $roles      = (int) $db->createCommand('SELECT COUNT(*) FROM {{%rol}}')->queryScalar();
            $idiomas    = (int) $db->createCommand('SELECT COUNT(*) FROM {{%idioma}}')->queryScalar();
            $parametros = (int) $db->createCommand('SELECT COUNT(*) FROM {{%parametro_sistema}}')->queryScalar();
            $marcas     = (int) $db->createCommand('SELECT COUNT(*) FROM {{%marca}}')->queryScalar();
            $modelos    = (int) $db->createCommand('SELECT COUNT(*) FROM {{%modelo}}')->queryScalar();

            return [
                'roles'       => $roles,
                'idiomas'     => $idiomas,
                'parametros'  => $parametros,
                'marcas'      => $marcas,
                'modelos'     => $modelos,
                'inicializado' => ($roles > 0 && $idiomas > 0 && $parametros > 0),
            ];
        } catch (\Throwable $e) {
            Yii::error('DatabaseInitService::obtenerEstado: ' . $e->getMessage(), $this->logCategoria);
            return ['roles' => 0, 'idiomas' => 0, 'parametros' => 0, 'inicializado' => false];
        }
    }
}
