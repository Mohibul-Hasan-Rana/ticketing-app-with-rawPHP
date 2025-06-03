<?php

    header('Content-Type: application/json');

    require_once 'helpers/Database.php';
    require_once 'helpers/Response.php';
    require_once 'middleware/Auth.php';
    require_once 'middleware/RateLimit.php';
    require_once 'controllers/UserController.php';
    require_once 'controllers/DepartmentController.php';
    require_once 'controllers/TicketController.php';

    
    $database = new Database();
    $db = $database->getConnection();
    $database->createTables();

    $rateLimit = new RateLimit($db);

    $request_method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path = trim($path, '/');
    $path_parts = explode('/', $path);

    if ($path_parts[0] === 'index.php') {
        array_shift($path_parts);
    }

    $endpoint = $path_parts[0] ?? '';
    $resource_id = $path_parts[1] ?? null;
    $action = $path_parts[2] ?? null;

    

    try {
        // Apply rate limiting to ticket submission
        if ($endpoint === 'tickets' && $request_method === 'POST') {
            if (!$rateLimit->check($_SERVER['REMOTE_ADDR'], 'ticket_submit', 5, 300)) { 
                Response::error('Rate limit exceeded. Please try again later.', 429);
                exit();
            }
        }

        // Route the request
        switch ($endpoint) {
            case 'users':
                $controller = new UserController($db);
                break;
            case 'departments':
                $controller = new DepartmentController($db);
                break;
            case 'tickets':
                $controller = new TicketController($db);
                break;
            case 'auth':
                $controller = new UserController($db);
                if ($resource_id === 'login') {
                    $controller->login();
                    exit();
                } elseif ($resource_id === 'logout') {
                    $controller->logout();
                    exit();
                } elseif ($resource_id === 'register') {
                    $controller->register();
                    exit();
                }
                break;
            default:
                Response::error('Endpoint not found', 404);
                exit();
        }

        // Handle the request based on method and parameters
        switch ($request_method) {
            case 'GET':
                if ($resource_id) {
                    if ($action) {
                        $controller->handleAction($resource_id, $action);
                    } else {
                        $controller->show($resource_id);
                    }
                } else {
                    $controller->index();
                }
                break;
            case 'POST':
                if ($action) {
                    $controller->handleAction($resource_id, $action);
                } else {
                    $controller->create();
                }
                break;
            case 'PUT':
                $controller->update($resource_id);
                break;
            case 'DELETE':
                $controller->delete($resource_id);
                break;
            default:
                Response::error('Method not allowed', 405);
                break;
        }
    } catch (Exception $e) {
        Response::error('Internal server error: ' . $e->getMessage(), 500);
    }

?>