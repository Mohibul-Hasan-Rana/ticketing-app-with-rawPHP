## API Endpoints

### Authentication
```
POST /auth/register    - Register new user
POST /auth/login       - Login user
POST /auth/logout      - Logout user
```

### Users
```
GET    /users          - List all users (admin only)
GET    /users/{id}     - Get user details
POST   /users          - Create user (admin only)
PUT    /users/{id}     - Update user
DELETE /users/{id}     - Delete user (admin only)
```

### Departments
```
GET    /departments     - List departments
GET    /departments/{id} - Get department
POST   /departments     - Create department (admin only)
PUT    /departments/{id} - Update department (admin only)
DELETE /departments/{id} - Delete department (admin only)
```

### Tickets
```
GET    /tickets              - List tickets
GET    /tickets/{id}         - Get ticket details
POST   /tickets              - Create ticket
PUT    /tickets/{id}         - Update ticket
DELETE /tickets/{id}         - Delete ticket
POST   /tickets/{id}/notes   - Add note to ticket
GET    /tickets/{id}/notes   - Get ticket notes
POST   /tickets/{id}/assign  - Assign agent to ticket
```

## Setup Instructions

### 1. Database Setup
Create a MySQL database named `ticketing_system`. The tables will be created automatically when you first run the application.

### 2. Configuration
Update the database credentials in `helpers/Database.php`:
```php
private $host = 'localhost';
private $db_name = 'ticketing_system';
private $username = 'your_username';
private $password = 'your_password';
```

## Project Structure
```
├── controllers/
│   ├── UserController.php    # User operations
│   ├── DepartmentController.php
│   └── TicketController.php
├── models/
│   ├── User.php             
│   ├── Department.php
│   └── Ticket.php
├── middleware/
│   ├── Auth.php             # Authentication middleware
│   └── RateLimit.php        # Rate limiting
├── helpers/
│   └── Response.php         # API response helper
|   └── Database.php          # Database configuration
├── .htaccess               # URL rewriting
├── index.php               # Main entry point
└── README.md               # How to setup and API endpoints
```
