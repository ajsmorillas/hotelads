<?php
// Plantilla versionada. El archivo real (config.php) NO se versiona (ver .gitignore)
// y vive fuera del repo Git en el servidor: un directorio hermano de dist/, es decir
// dirname($_SERVER['DOCUMENT_ROOT']) . '/rateshopper-config/config.php'.
// Así sobrevive a cualquier rebuild (`npm run build` limpia dist/ en cada deploy,
// pero nunca toca esta carpeta porque está fuera del árbol que gestiona Astro).
//
// Despliegue: crea manualmente en el servidor la carpeta rateshopper-config/
// (hermana de dist/, dentro de httpdocs/) y copia este archivo como config.php,
// rellenando los placeholders reales.

return [
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'TU_NOMBRE_BD',
        'user' => 'TU_USUARIO_BD',
        'pass' => 'TU_PASSWORD_BD',
    ],

    'serpapi' => [
        'api_key' => 'TU_API_KEY_SERPAPI', // https://serpapi.com/manage-api-key
    ],

    // Los 8 hoteles a comparar. property_token obtenido vía Google Hotels API (SerpApi).
    // Esta lista no es sensible, por eso va también en el config.example.php versionado.
    'hoteles' => [
        'eldorado_sanjose' => [
            'nombre'         => 'El Dorado San José',
            'propio'         => true,
            'property_token' => 'ChgIhovFtKeG0Kf2ARoLL2cvMXRtYnhnbmgQAQ',
        ],
        'calachica_lasnegras' => [
            'nombre'         => 'Calachica Las Negras Hotel Boutique',
            'propio'         => true,
            'property_token' => 'ChcI8MvLwduHlupDGgsvZy8xdHBfN3JoYxAB',
        ],
        'hostal_elcabo' => [
            'nombre'         => 'Hostal El Cabo',
            'propio'         => false,
            'property_token' => 'ChgI8vePpcfF_OmTARoLL2cvMXRmY181NjYQAQ',
        ],
        'mc_sanjose' => [
            'nombre'         => 'MC San José',
            'propio'         => false,
            'property_token' => 'ChcIrtHam8DQ9edAGgsvZy8xdGR6MTlsehAB',
        ],
        'sol_bahia_sanjose' => [
            'nombre'         => 'Sol Bahía San José',
            'propio'         => false,
            'property_token' => 'ChgIvaDD5-fHkaBiGgwvZy8xaGMydDBfaHIQAQ',
        ],
        'hotel_naturaleza_rodalquilar' => [
            'nombre'         => 'Hotel de Naturaleza Rodalquilar',
            'propio'         => false,
            'property_token' => 'ChgIwayUhtTyrLt6GgwvZy8xaGMxMmNyMWwQAQ',
        ],
        'lasnegras_olivencia' => [
            'nombre'         => 'Las Negras Olivencia Natural',
            'propio'         => false,
            'property_token' => 'ChoIrf6Xn4_8zorcARoNL2cvMTF6N2NjZDBtdBAB',
        ],
        'hotel_senderos_aguamarga' => [
            'nombre'         => 'Hotel Senderos Aguamarga',
            'propio'         => false,
            'property_token' => 'ChgIztnPg-LZjO5pGgwvZy8xMWgwODRnNmYQAQ',
        ],
    ],
];
