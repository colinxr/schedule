# Artist Scheduling System

A Laravel-based REST API for managing artist availability and scheduling appointments. This system provides efficient time slot management with features like pagination, customizable durations, and buffer times between appointments.

## Features

- ✨ Find available time slots for artists
- 📅 Smart scheduling with customizable appointment durations
- ⏰ Buffer time support between appointments
- 🌍 Timezone handling
- 📊 Paginated results
- 🔍 Filtering options (morning/afternoon/evening preferences)
- 🚨 Emergency booking support
- ✅ Input validation and error handling

## Requirements

- PHP 8.1 or higher
- Laravel 10.x
- MySQL 8.0 or higher
- Composer

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd [project-directory]
```

2. Install dependencies:
```bash
composer install
```

3. Copy the environment file:
```bash
cp .env.example .env
```

4. Configure your database in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run migrations:
```bash
php artisan migrate
```

## API Documentation

### Get Available Slots

Retrieves available time slots for a specific artist.

```
GET /api/artists/{artist}/available-slots
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| duration | integer | Yes | Appointment duration in minutes (30-480) |
| date | date | No | Date to check availability (defaults to today) |
| page | integer | No | Page number for pagination (default: 1) |
| per_page | integer | No | Results per page (1-50, default: 10) |
| timezone | string | No | Client timezone |
| preferred_time | string | No | Preference for morning/afternoon/evening |
| emergency | boolean | No | Flag for emergency bookings |
| buffer | integer | No | Buffer time in minutes between appointments (0-120) |
| limit | integer | No | Maximum number of slots to return (1-100) |

#### Response

```json
{
    "available_slots": [
        {
            "start": "2024-01-20T09:00:00Z",
            "end": "2024-01-20T10:00:00Z"
        }
    ],
    "pagination": {
        "total": 24,
        "total_pages": 3,
        "has_more_pages": true,
        "current_page": 1,
        "per_page": 10,
        "from": 1,
        "to": 10
    }
}
```

#### Error Responses

- 404: Artist not found or user is not an artist
- 422: Validation errors

## Testing

Run the test suite:

```bash
php artisan test
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
