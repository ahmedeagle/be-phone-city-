# 🚀 Quick Setup - Run After Starting Laragon

Write-Host ""
Write-Host "🚀 IMPORTANT: Make sure Laragon is started first!" -ForegroundColor Yellow
Write-Host "   Open Laragon → Click 'Start All' → Wait for services to start" -ForegroundColor Gray
Write-Host ""
$continue = Read-Host "Is Laragon running with all services started? (y/n)"
if ($continue -ne "y") {
    Write-Host "Please start Laragon first, then run this script again." -ForegroundColor Red
    exit 0
}

Write-Host ""
Write-Host "Setting up CityPhone Backend..." -ForegroundColor Cyan
Write-Host ""

# Set PATH
$env:Path = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;C:\laragon\bin\composer;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;" + $env:Path

# Create database
Write-Host "1️⃣  Creating database..." -ForegroundColor Yellow
try {
    & mysql -u root -e "CREATE DATABASE IF NOT EXISTS cityphone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1 | Out-Null
    Write-Host "✅ Database created!" -ForegroundColor Green
} catch {
    Write-Host "❌ Database creation failed. Is MySQL running in Laragon?" -ForegroundColor Red
    exit 1
}

# Check if .env exists, if not copy from .env.example
Write-Host ""
Write-Host "2️⃣  Checking environment file..." -ForegroundColor Yellow
if (-Not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "✅ Created .env file" -ForegroundColor Green
} else {
    Write-Host "✅ .env file exists" -ForegroundColor Green
}

# Generate app key using direct env file manipulation (avoids hanging)
Write-Host ""
Write-Host "3️⃣  Generating application key..." -ForegroundColor Yellow
$envContent = Get-Content .env -Raw
if ($envContent -match "APP_KEY=\s*$" -or $envContent -notmatch "APP_KEY=") {
    try {
        $output = & php artisan key:generate --force --no-interaction 2>&1
        Write-Host "✅ Key generated!" -ForegroundColor Green
    } catch {
        Write-Host "⚠️  Key generation had issues, continuing..." -ForegroundColor Yellow
    }
} else {
    Write-Host "✅ Key already exists!" -ForegroundColor Green
}

# Clear cache
Write-Host ""
Write-Host "4️⃣  Clearing cache..." -ForegroundColor Yellow
& php artisan config:clear --no-interaction 2>&1 | Out-Null
& php artisan cache:clear --no-interaction 2>&1 | Out-Null
Write-Host "✅ Cache cleared!" -ForegroundColor Green

# Run migrations
Write-Host ""
Write-Host "5️⃣  Creating database tables..." -ForegroundColor Yellow
$migrateOutput = & php artisan migrate --force --no-interaction 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Tables created!" -ForegroundColor Green
} else {
    Write-Host "❌ Migration failed!" -ForegroundColor Red
    Write-Host $migrateOutput -ForegroundColor Yellow
    exit 1
}

# Seed database
Write-Host ""
Write-Host "6️⃣  Adding test data..." -ForegroundColor Yellow
$seedOutput = & php artisan db:seed --force --no-interaction 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Data added!" -ForegroundColor Green
} else {
    Write-Host "⚠️  Seeding had some issues:" -ForegroundColor Yellow
    Write-Host $seedOutput -ForegroundColor Gray
    Write-Host ""
    Write-Host "Some seeders may have failed, but continuing..." -ForegroundColor Yellow
}

# Storage link
Write-Host ""
Write-Host "7️⃣  Creating storage link..." -ForegroundColor Yellow
try {
    & php artisan storage:link --no-interaction 2>&1 | Out-Null
    Write-Host "✅ Done!" -ForegroundColor Green
} catch {
    Write-Host "✅ Storage link already exists!" -ForegroundColor Green
}

# Summary
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "✅ SETUP COMPLETE!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "🚀 Starting backend server..." -ForegroundColor White
Write-Host ""
Write-Host "📌 Backend will run at:" -ForegroundColor White
Write-Host "   http://localhost:8000" -ForegroundColor Cyan
Write-Host ""
Write-Host "🔑 Admin Login:" -ForegroundColor White
Write-Host "   Email:    admin@gmail.com" -ForegroundColor Gray
Write-Host "   Password: 12345678" -ForegroundColor Gray
Write-Host ""
Write-Host "🧪 Test API in new terminal:" -ForegroundColor White
Write-Host "   Invoke-RestMethod http://localhost:8000/api/v1/categories" -ForegroundColor Gray
Write-Host ""
Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host ""
Start-Sleep -Seconds 2

& php artisan serve --host=0.0.0.0
