<?php

return [

	/*
	|--------------------------------------------------------------------------
	| Paths
	|--------------------------------------------------------------------------
	|
	*/

	'path' => [

		'migration' => base_path('database/migrations/'),

		'model' => app_path('Models/'),

		'datatables' => app_path('DataTables/'),

		'repository' => app_path('Repositories/'),

		'service' => app_path('Services/'),

		'trait' => app_path('Traits/'),

		'routes' => base_path('routes/web.php'),

		'api_routes' => base_path('routes/api.php'),

		'request' => app_path('Http/Requests/'),

		'api_request' => app_path('Http/Requests/API/'),

		'controller' => app_path('Http/Controllers/'),

		'api_controller' => app_path('Http/Controllers/API/'),

		'test_trait' => base_path('tests/Traits/'),

		'repository_test' => base_path('tests/Feature/'),

		'api_test' => base_path('tests/Feature/'),

		'views' => base_path('resources/views/'),

		'schema_files' => base_path('resources/model_schemas/'),

		'templates_dir' => base_path('resources/thomisticus/thomisticus-generator-templates/'),

		'modelJs' => base_path('resources/assets/js/models/'),
	],

	/*
	|--------------------------------------------------------------------------
	| Namespaces
	|--------------------------------------------------------------------------
	|
	*/

	'namespace' => [

		'model' => 'App\Models',

		'datatables' => 'App\DataTables',

		'repository' => 'App\Repositories',

		'service' => 'App\Services',

		'trait' => 'App\Traits',

		'controller' => 'App\Http\Controllers',

		'api_controller' => 'App\Http\Controllers\API',

		'request' => 'App\Http\Requests',

		'api_request' => 'App\Http\Requests\API',
	],

	/*
	|--------------------------------------------------------------------------
	| Templates
	|--------------------------------------------------------------------------
	|
	*/

	'templates' => 'adminlte-templates',

	/*
	|--------------------------------------------------------------------------
	| Model extend class
	|--------------------------------------------------------------------------
	|
	*/

	'model_extend_class' => 'Illuminate\Database\Eloquent\Model',

	/*
	|--------------------------------------------------------------------------
	| API routes prefix & version
	|--------------------------------------------------------------------------
	|
	*/

	'api_prefix' => 'api',

	'api_version' => 'v1',

	/*
	|--------------------------------------------------------------------------
	| Options
	|--------------------------------------------------------------------------
	|
	*/

	'options' => [

		'softDelete' => true,

		'tables_searchable_default' => false,
	],

	/*
	|--------------------------------------------------------------------------
	| Prefixes
	|--------------------------------------------------------------------------
	|
	*/

	'prefixes' => [

		'route' => '',  // using admin will create route('admin.?.index') type routes

		'path' => '',

		'view' => '',  // using backend will create return view('backend.?.index') type the backend views directory

		'public' => '',
	],

	/*
	|--------------------------------------------------------------------------
	| Add-Ons
	|--------------------------------------------------------------------------
	|
	*/

	'add_on' => [

		'swagger' => false,

		'tests' => true,

		'datatables' => false,

		'menu' => [

			'enabled' => false,

			'menu_file' => 'layouts/menu.blade.php',
		],
	],

	/*
	|--------------------------------------------------------------------------
	| Timestamp Fields
	|--------------------------------------------------------------------------
	|
	*/

	'timestamps'          => [

		'enabled' => true,

		'created_at' => 'dh_criado_em',

		'updated_at' => 'dh_atualizado_em',

		'deleted_at' => 'dh_deletado_em',
	],

	/*
	|--------------------------------------------------------------------------
	| Save model files to `App/Models` when use `--prefix`. see #208
	|--------------------------------------------------------------------------
	|
	*/
	'ignore_model_prefix' => false,

];
