# City Phone AI Chatbot Implementation

## Overview

This document describes the AI-powered chatbot implementation for the City Phone e-commerce application. The chatbot provides intelligent assistance for both guest and authenticated users, helping them find products, get information, and perform various actions.

## Architecture

The chatbot is built using:
- **OpenAI GPT-4 Turbo** for natural language understanding and generation
- **Function Calling** for structured tool execution
- **Laravel Sanctum** for authentication
- **Custom middleware** for rate limiting and security

### Key Components

1. **ChatbotController** - API endpoints for chat interactions
2. **ChatbotService** - Core orchestration and OpenAI integration
3. **Tools** - Modular functions that the AI can invoke
4. **Middleware** - Rate limiting and security
5. **Models** - Conversation and message persistence

## API Endpoints

### POST `/api/v1/chatbot/chat`
Send a message to the chatbot (available for guests and authenticated users).

**Request:**
```json
{
  "message": "Show me iPhone products",
  "session_id": "optional-session-id",
  "language": "en"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Chat response generated successfully",
  "data": {
    "message": "Here are the iPhone products...",
    "session_id": "uuid",
    "conversation_id": 1
  }
}
```

**Rate Limits:**
- Guests: 20 requests per minute
- Authenticated users: 60 requests per minute

### GET `/api/v1/chatbot/history/{sessionId}`
Get conversation history (requires authentication).

**Response:**
```json
{
  "status": true,
  "message": "Conversation history retrieved successfully",
  "data": {
    "messages": [
      {
        "role": "user",
        "content": "Hello",
        "created_at": "2026-02-18T00:00:00Z"
      },
      {
        "role": "assistant",
        "content": "Hi! How can I help you?",
        "created_at": "2026-02-18T00:00:01Z"
      }
    ],
    "session_id": "uuid"
  }
}
```

### DELETE `/api/v1/chatbot/clear/{sessionId}`
Clear conversation history (requires authentication).

## Available Tools

The chatbot can invoke the following tools based on user requests:

### Public Tools (Available to All Users)

#### 1. Search Catalog
Search for products, categories, or offers.

**Capabilities:**
- Search by name or description
- Filter by type (product, category, offer)
- Limit results

**Example Usage:**
- "Show me iPhone products"
- "Find smartphones under 3000 SAR"
- "What categories do you have?"

#### 2. Get Page Content
Retrieve static page content or about information.

**Capabilities:**
- Get page content by slug
- Get about us information
- Access terms, privacy policy, etc.

**Example Usage:**
- "Tell me about your company"
- "What is your return policy?"
- "Show me your contact information"

### Authenticated Tools (Require Login)

#### 3. Create Ticket
Create a support ticket.

**Capabilities:**
- Create tickets with subject and description
- Set priority and type
- Automatic ticket number generation

**Example Usage:**
- "I have a problem with my order"
- "Create a support ticket about payment issue"

#### 4. Add to Cart
Add products to shopping cart.

**Capabilities:**
- Add products with quantity
- Handle product options/variants
- Stock validation
- Price calculation

**Example Usage:**
- "Add iPhone 15 Pro to my cart"
- "Add 2 units of product ID 5 to cart"

#### 5. Add to Favorites
Save products to wishlist.

**Capabilities:**
- Add products to favorites
- Duplicate detection

**Example Usage:**
- "Save this product for later"
- "Add to my wishlist"

#### 6. Get My Tickets
View support tickets.

**Capabilities:**
- Filter by status
- Limit results
- View ticket details

**Example Usage:**
- "Show my support tickets"
- "What's the status of my tickets?"

#### 7. Get My Orders
View order history.

**Capabilities:**
- Filter by status
- View order details
- Track shipments

**Example Usage:**
- "Show my orders"
- "Where is my order?"
- "Show pending orders"

## Security Features

### 1. Rate Limiting
- **Guests**: 20 requests/minute per IP
- **Authenticated**: 60 requests/minute per user
- Custom middleware with X-RateLimit headers

### 2. Input Sanitization
- HTML tags stripped from messages
- Trimming and validation
- Maximum message length: 2000 characters

### 3. Authentication Checks
- Tools requiring authentication return errors for guests
- User ownership validation for all user-specific operations
- Session isolation between users

### 4. Logging
- All tool invocations logged
- Error tracking with context
- No sensitive data in logs

### 5. Validation
- Request validation using Form Requests
- Tool argument validation
- Database constraint validation

## Database Schema

### chatbot_conversations
```sql
- id (bigint, primary key)
- user_id (bigint, nullable, foreign key)
- session_id (string, unique)
- metadata (json, nullable)
- last_activity_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)
```

### chatbot_messages
```sql
- id (bigint, primary key)
- conversation_id (bigint, foreign key)
- role (enum: user, assistant, system, tool)
- content (text)
- tool_calls (json, nullable)
- metadata (json, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

## Configuration

### Environment Variables

Add to `.env`:
```env
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_ORGANIZATION=
```

### OpenAI Configuration

The chatbot uses:
- **Model**: gpt-4-turbo-preview
- **Temperature**: 0.7
- **Max Tokens**: 1000
- **Function Calling**: Enabled

## Multilingual Support

The chatbot supports both Arabic and English:
- System messages adapt to selected language
- Tool responses maintain language consistency
- Database stores both Arabic and English content

## Testing

Run the test suite:
```bash
php artisan test --filter ChatbotTest
```

### Test Coverage
- ✅ Guest chat messages
- ✅ Authenticated chat messages
- ✅ Message validation
- ✅ Conversation continuity
- ✅ History retrieval
- ✅ User isolation
- ✅ Conversation clearing
- ✅ Rate limiting
- ✅ Input sanitization
- ✅ Language validation
- ✅ Activity tracking

## Usage Examples

### Example 1: Product Search (Guest)
```
User: "Show me iPhone products"
Bot: "I found 3 iPhone products:
1. iPhone 15 Pro - 5000 SAR
2. iPhone 15 - 4500 SAR
3. iPhone 14 - 3500 SAR
Would you like more details about any of these?"
```

### Example 2: Add to Cart (Authenticated)
```
User: "Add iPhone 15 Pro to my cart"
Bot: "I've added iPhone 15 Pro to your cart. 
Price: 5000 SAR
Quantity: 1
Would you like to continue shopping or proceed to checkout?"
```

### Example 3: Order Tracking (Authenticated)
```
User: "Where is my order?"
Bot: "You have 2 orders:
1. Order #ORD-12345678 - Shipped (Tracking: TRK123)
2. Order #ORD-87654321 - Processing
Would you like details about a specific order?"
```

### Example 4: Support Ticket (Authenticated)
```
User: "I have a problem with my payment"
Bot: "I'll create a support ticket for you. Could you please describe the payment issue in detail?"
User: "My payment was deducted but order shows as pending"
Bot: "I've created ticket #TKT-20260218-000001 for your payment issue. 
Priority: High
Our support team will respond within 24 hours."
```

## Extending the Chatbot

### Adding New Tools

1. Create a new tool class in `app/Ai/Tools/`:

```php
<?php

namespace App\Ai\Tools;

use App\Models\User;

class MyNewTool extends BaseTool
{
    public static function getName(): string
    {
        return 'my_new_tool';
    }

    public static function getDefinition(): array
    {
        return [
            'name' => self::getName(),
            'description' => 'Description of what this tool does',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'param1' => [
                        'type' => 'string',
                        'description' => 'Parameter description',
                    ],
                ],
                'required' => ['param1'],
            ],
        ];
    }

    public function execute(array $arguments, ?User $user): array
    {
        // Tool logic here
        return $this->success(['result' => 'data']);
    }
}
```

2. Register the tool in `ChatbotService::initializeTools()`:

```php
$this->registerTool(
    MyNewTool::getName(),
    MyNewTool::class,
    MyNewTool::getDefinition()
);
```

## Monitoring and Maintenance

### Logs
Check chatbot activity in Laravel logs:
```bash
tail -f storage/logs/laravel.log | grep "Tool"
```

### Database Cleanup
Old conversations can be cleaned up periodically:
```php
// Delete conversations older than 30 days
ChatbotConversation::where('last_activity_at', '<', now()->subDays(30))->delete();
```

### Performance Monitoring
- Monitor OpenAI API response times
- Track tool execution times
- Monitor rate limit hits

## Troubleshooting

### Common Issues

**Issue**: "OpenAI API error"
- **Solution**: Check OPENAI_API_KEY in .env
- Verify API key has sufficient credits

**Issue**: "Tool not found"
- **Solution**: Ensure tool is registered in ChatbotService
- Check tool class name and namespace

**Issue**: "Rate limit exceeded"
- **Solution**: Wait for rate limit to reset
- Increase limits in ChatbotRateLimitMiddleware if needed

**Issue**: "Authentication required"
- **Solution**: User must be logged in for authenticated tools
- Check Sanctum token is valid

## Future Enhancements

Potential improvements:
- [ ] Streaming responses for real-time chat
- [ ] Voice input/output support
- [ ] Image analysis capabilities
- [ ] Multi-language detection
- [ ] Conversation analytics dashboard
- [ ] Custom training on product catalog
- [ ] Integration with WhatsApp/Telegram
- [ ] Sentiment analysis
- [ ] Automated follow-ups

## Support

For issues or questions about the chatbot implementation, contact the development team or create a ticket in the project repository.
