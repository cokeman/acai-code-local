<?php
class ApiJsonBuilder {
    static $jsonConfig = [];
    static $ignoreTables = ["accounts"];
    static $ignoreSchemaTypes = ["separator", "upload"];

    static function generateBaseConfig($options = []){
        
        // $options = [
        //     "apartados" => [
        //         "GET" => false,
        //         "GETALL" => true,
        //         "SEARCH" => false,
        //         "POST" => false,
        //         "PATCH" => false,
        //         "DELETE" => false,
        //         "HIDDEN_FIELDS" => "num,breadcrumb",
        //         "NOT_EDITABLE" => "name"
        //     ]
        // ];
        $schemaFiles = array_slice(scandir(__DIR__.'/../../../../../data/schema/'), 2);

        self::$jsonConfig = [
            "host" => "https://".$_SERVER["HTTP_HOST"],
            "basePath" => "/cms/lib/plugins/cms_api/v3/",
            "title" => "CMS API de Coco Solution",
            "info" => [],
            "variables" => [
                "baseHeaders" => self::getBaseHeaders()
            ]
        ];

        $endPoints = [];
        self::generateBaseCustom($endPoints);
        foreach($schemaFiles as $schemaFile){
            $tableName = str_replace(".ini.php","",$schemaFile);
            if(in_array($tableName, self::$ignoreTables)) continue;
            $schema = loadSchema($tableName);
            $endPoints[$tableName] = [
                "title"             =>      @$schema["menuName"]?:"",
                "description"       =>      @$schema["menuDesc"]?:"",
                "hiddenFields"      =>      self::explodeFields(@$options[$tableName]['HIDDEN_FIELDS']),
                "notEditableFields" =>      self::explodeFields(@$options[$tableName]['NOT_EDITABLE']),
                "methods"           =>      [
                    "GET" => self::generateBaseMethod($tableName,$schema),
                    "POST" => self::generateBaseMethod($tableName,$schema,["body"], true)
                ],
                "search"            =>      [
                    "methods" => [
                        "POST" => self::generateBaseSearch($tableName,$schema,["body"])
                    ]
                ],
                ":"                 =>      [
                    "variable" => "num",
                    "methods" => [
                        "GET" => self::generateBaseMethod($tableName,$schema),
                        "PATCH" => self::generateBaseMethod($tableName,$schema,["body"]),
                        "DELETE" => self::generateBaseMethod($tableName,$schema)
                    ]
                ]
            ];
            if(isset($options[$tableName]) && !$options[$tableName]["GETALL"]) unset($endPoints[$tableName]["methods"]["GET"]);
            if(isset($options[$tableName]) && !$options[$tableName]["POST"]) unset($endPoints[$tableName]["methods"]["POST"]);
            if(isset($options[$tableName]) && !$options[$tableName]["GET"]) unset($endPoints[$tableName][":"]["methods"]["GET"]);
            if(isset($options[$tableName]) && !$options[$tableName]["PATCH"]) unset($endPoints[$tableName][":"]["methods"]["PATCH"]);
            if(isset($options[$tableName]) && !$options[$tableName]["DELETE"]) unset($endPoints[$tableName][":"]["methods"]["DELETE"]);
            if(!count($endPoints[$tableName][":"]["methods"])) unset($endPoints[$tableName][":"]);
            if(isset($options[$tableName]) && !$options[$tableName]["SEARCH"]) unset($endPoints[$tableName]["search"]);
        }
        

        self::$jsonConfig["endPoints"] = $endPoints;
        file_put_contents("schemaBase.json", json_encode(self::$jsonConfig));
        return self::$jsonConfig;
    }

    private static function explodeFields($fields) {
        if (!$fields) return [];
        return array_map('trim', array_values(array_filter(explode(',', $fields))));
    }

    private static function generateBaseMethod($tableName, $schema, $types = [], $checkRequireds = false){
        $result = ["headers" => self::getBaseHeaders()];
        if(in_array("body", $types)) {
            $fields_from_table = [
                "options" => [
                    "forceNum" => false,
                    "ignoreSchema" => false,
                    "return_last_id" => false,
                    "dieBeforeQuery" => false,
                    "generate_category_metadata" => false,
                    "prefix" => '$TABLE_PREFIX'
                ]
            ];
            foreach ($schema as $schemaFieldName => $schemaField) {
                if(is_array($schemaField)) {
                    if(
                        (isset($schemaField["type"]) && in_array($schemaField["type"], self::$ignoreSchemaTypes))
                        || isset($schemaField['adminOnly'])
                        || isset($schemaField['isSystemField'])
                    ) { continue; }
                    $field_value = ["value" => "", "required" => false];
                    if(isset($schemaField["type"])) {
                        $field_value["schema"] = $schemaField;
                        switch ($schemaField["type"]) {
                            case 'checkbox':
                                $field_value["default"] = intval($schemaField['checkedByDefault']);
                                break;
                            default:
                                # code...
                                break;
                        }
                    }
                    if(isset($schemaField["defaultValue"]) && !empty($schemaField["defaultValue"])) $field_value["default"] = $schemaField["defaultValue"];
                    if($checkRequireds && isset($schemaField["isRequired"]) && $schemaField["isRequired"]) $field_value["required"] = true;
                    if(isset($schemaField["customColumnType"]) && $schemaField["customColumnType"]) $field_value["field_type"] = $schemaField["customColumnType"];
                    $fields_from_table[$schemaFieldName] = $field_value;
                }
            }
            $result["body"] = ["data" => $fields_from_table];
        }
        return $result;
    }
    private static function generateBaseSearch($tableName, $schema, $types = [], $checkRequireds = false){
        $result = [
            "headers" => self::getBaseHeaders(),
            "body" => [
                $tableName => [
                    "where" => [
                        //"value" => [
                        //    ["column" => "num", "value" => 1, "operator" => "="]
                        //],
                        "required" => false,
                        "docs" => [
                            "Valores posibles de operator: (=, like, !=)"
                        ]
                    ],
                    "order" => [
                        "value" => "num DESC",
                        "required" => false
                    ],
                    "limit" => [
                        //"value" => 25,
                        "required" => false,
                        "docs" => [
                            "Valores posibles: String (Solo limit): 10",
                            "Valores posibles: String (Limit con offset): 0,10",
                            "Valores posibles: Array: ['limit' => 10, 'page' => 1]"
                        ]
                    ]
                ]
            ]                
        ];
    
        return $result;
    }

    private static function generateBaseCustom(&$endPoints) {
        $endPoints["auth"] = [
            "title" => "Login",
            "description" => "Petición de Login. Sólo debe de ser ejecutado la primera vez para obtener el token. Una vez recogido el token se insertará en las cabeceras Authorization:Bearer {{token}}",
            "methods" => [
                "POST" => [
                    "headers" => [
                        "Content-length" => [
                            "value" => "0",
                            "description" => "",
                            "required" => true
                        ],
                        "Content-type" => [
                            "value" => "application/json",
                            "required" => true
                        ],
                        "Authorization" => [
                            "value" => "Login {{BASE64ENCODE(user:pass)}}",
                            "required" => true
                        ]
                    ]
                ]
            ]
        ];
        $endPoints["bulk"] = [
            "title" => "Registros en masa",
            "description" => "Se realizan acciones a varios registros de varias tablas",
            "controller" => "Bulk",
            "methods" => [
                "POST" => [
                    "headers" => self::getBaseHeaders(),
                    "body" => [
                        "{tableName}" => [
                            "where" => [
                                "value" => [
                                    ["column" => "num", "value" => 1, "operator" => "="]
                                ],
                                "required" => false,
                                "docs" => [
                                    "Valores posibles de operator: (=, like, !=)"
                                ]
                            ],
                            "order" => [
                                "value" => "num DESC",
                                "required" => false
                            ],
                            "limit" => [
                                "value" => 10,
                                "required" => false
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $endPoints["bulk_sync"] = [
            "title" => "Sincronización de registros en masa",
            "description" => "Se realizan acciones de inserción o actualización a varios registros de varias tablas utilizando la clave primaria num como referencia",
            "controller" => "Bulk",
            "methods" => [
                "POST" => [
                    "headers" => self::getBaseHeaders(),
                    "body" => [
                        "{tableName}" => [
                            "records" => []
                        ],
                        "{example}" => [
                            "{tableName}" => [
                                "records" => [
                                    [
                                        "num" =>  '{num1}',
                                        "{field}" =>  '{value}',
                                        "options"=>  [
                                            "forceNum"=>  true
                                        ]
                                    ],
                                    [
                                        "num" =>  '{num2}',
                                        "{field}" =>  '{value}',
                                    ],
                                    [
                                        "{field}" =>  '{value}',
                                    ]
                                ],
                                "options" => [
                                    "generate_category_metadata" => true
                                ],
                                "#comentario" =>  "En este ejemplo el primer es un num forzado, <br>          el segundo un update y el tercero una insercion"
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $endPoints["upload"] = [
            "title" => "Upload",
            "description" => "Subida de ficheros al servidor",
            "controller" => "Files",
            "methods" => [
                "POST" => [
                    "headers" => [
                        "Content-type" => [
                            "value" => "multipart/form-data",
                            "required" => true
                        ],
                        "Authorization" => [
                            "value" => "Bearer {{TOKEN}}",
                            "required" => true
                        ]
                    ],
                    "body" => [
                        "field" => ["required" => true,"value" => "file","default" => "file"],
                        "file" => ["required" => false,"value" => "file (blob). O este campo o file_b64 son obligatorios"],
                        "file_b64" => ["required" => false,"value" => "file (base64)"],
                        "filename" => ["required" => false,"value" => "filename, requerido si se envía file_b64"],
                        "options" => ["required" => false,"value" => "array de opciones ( Info : https://github.com/verot/class.upload.php )"]
                    ]
                ]
            ]
        ];
        
    }

    private static function getBaseHeaders() {
        return [
            "Content-type" => [
                "value" => "application/json",
                "required" => true
            ],
            "Authorization" => [
                "value" => "Bearer {{TOKEN}}",
                "required" => true
            ]
        ];
    }
}