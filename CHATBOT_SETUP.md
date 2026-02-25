# Chatbot Setup Guide

## Quick Start

Follow these steps to get the chatbot up and running:

### 1. Install Dependencies

The OpenAI PHP client is already installed. If you need to reinstall:

```bash
composer require openai-php/laravel
```

### 2. Configure OpenAI API Key

Add your OpenAI API key to `.env`:

```env
OPENAI_API_KEY=sk-your-actual-openai-api-key-here
OPENAI_ORGANIZATION=
```

**Important**: Replace `your_openai_api_key_here` with your actual OpenAI API key from https://platform.openai.com/api-keys

### 3. Run Migrations

The migrations are already run, but if you need to run them again:

```bash
php artisan migrate
```

This creates:
- `chatbot_conversations` table
- `chatbot_messages` table

### 4. Clear Cache

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 5. Test the Chatbot

#### Using cURL (Guest User):

```bash
curl -X POST http://your-domain.com/api/v1/chatbot/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "Show me iPhone products",
    "language": "en"
  }'
```

#### Using cURL (Authenticated User):

```bash
# First, login to get token
curl -X POST http://your-domain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'

# Then use the token
curl -X POST http://your-domain.com/api/v1/chatbot/chat \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "message": "Show my orders",
    "language": "en"
  }'
```

### 6. Run Tests

```bash
php artisan test --filter ChatbotTest
```

## Verification Checklist

- [ ] OpenAI API key is set in `.env`
- [ ] Migrations have been run successfully
- [ ] Database tables `chatbot_conversations` and `chatbot_messages` exist
- [ ] Can send a test message as guest
- [ ] Can send a test message as authenticated user
- [ ] Rate limiting is working (try sending 21 requests quickly)
- [ ] Tests pass successfully

## Frontend Integration Example

### JavaScript/Fetch Example:

```javascript
// Send a chat message
async function sendChatMessage(message, sessionId = null) {
  const response = await fetch('/api/v1/chatbot/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': `Bearer ${userToken}` // If authenticated
    },
    body: JSON.stringify({
      message: message,
      session_id: sessionId,
      language: 'en' // or 'ar'
    })
  });

  const data = await response.json();
  
  if (data.status) {
    return {
      message: data.data.message,
      sessionId: data.data.session_id
    };
  } else {
    throw new Error(data.message);
  }
}

// Usage
try {
  const result = await sendChatMessage('Show me iPhone products');
  console.log('Bot:', result.message);
  console.log('Session ID:', result.sessionId);
} catch (error) {
  console.error('Error:', error.message);
}
```

### React Example:

```jsx
import { useState } from 'react';

function Chatbot() {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [sessionId, setSessionId] = useState(null);
  const [loading, setLoading] = useState(false);

  const sendMessage = async () => {
    if (!input.trim()) return;

    // Add user message to UI
    const userMessage = { role: 'user', content: input };
    setMessages(prev => [...prev, userMessage]);
    setInput('');
    setLoading(true);

    try {
      const response = await fetch('/api/v1/chatbot/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          message: input,
          session_id: sessionId,
          language: 'en'
        })
      });

      const data = await response.json();

      if (data.status) {
        // Add bot response to UI
        const botMessage = { role: 'assistant', content: data.data.message };
        setMessages(prev => [...prev, botMessage]);
        setSessionId(data.data.session_id);
      } else {
        alert('Error: ' + data.message);
      }
    } catch (error) {
      alert('Error: ' + error.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="chatbot">
      <div className="messages">
        {messages.map((msg, idx) => (
          <div key={idx} className={`message ${msg.role}`}>
            {msg.content}
          </div>
        ))}
        {loading && <div className="loading">Thinking...</div>}
      </div>
      <div className="input-area">
        <input
          type="text"
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
          placeholder="Type your message..."
        />
        <button onClick={sendMessage} disabled={loading}>
          Send
        </button>
      </div>
    </div>
  );
}
```

## Troubleshooting

### Issue: "OpenAI API error: Invalid API key"

**Solution**: 
1. Check that your API key is correct in `.env`
2. Run `php artisan config:clear`
3. Verify the key at https://platform.openai.com/api-keys

### Issue: "Rate limit exceeded"

**Solution**:
- Wait 60 seconds before trying again
- For guests: limit is 20 requests/minute
- For authenticated users: limit is 60 requests/minute

### Issue: "Tool not found" error

**Solution**:
1. Check that all tool classes exist in `app/Ai/Tools/`
2. Verify tools are registered in `ChatbotService::initializeTools()`
3. Run `composer dump-autoload`

### Issue: Database errors

**Solution**:
```bash
php artisan migrate:fresh
php artisan db:seed # if you have seeders
```

## Production Deployment

### Before deploying to production:

1. **Set production OpenAI key**:
   ```env
   OPENAI_API_KEY=sk-your-production-key
   ```

2. **Optimize Laravel**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   composer install --optimize-autoloader --no-dev
   ```

3. **Set up monitoring**:
   - Monitor OpenAI API usage and costs
   - Set up alerts for rate limit hits
   - Monitor database growth

4. **Configure queue workers** (optional for better performance):
   ```bash
   php artisan queue:work
   ```

5. **Set up log rotation**:
   - Chatbot generates logs for all tool invocations
   - Configure log rotation to prevent disk space issues

## Support

If you encounter any issues:

1. Check the logs: `storage/logs/laravel.log`
2. Review the documentation: `CHATBOT_IMPLEMENTATION.md`
3. Run the tests to verify functionality
4. Contact the development team

## Next Steps

- Customize the chatbot's personality in `ChatbotService::getSystemMessage()`
- Add more tools for specific business needs
- Integrate with your frontend application
- Set up monitoring and analytics
- Configure automated conversation cleanup
