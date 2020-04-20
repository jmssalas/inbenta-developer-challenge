
# Inbenta Developer Challenge 2020 - Server side
This folder contains the server side of Inbenta Developer Challenge 2020. It is developed in PHP using Laravel 7.6.2.

To set the environment file, you should copy the `.env.example` file to `.env` file. This file contains the following environment variables which should be set correctly before start it:

- `INBENTA_API_KEY`: This is the API Key used as `x-inbenta-key` header.
- `INBENTA_SECRET`: This is the Secret key used for getting the authentication. 

In addition, this environment file contains the following variables (default: informed with the information of the documentation):

- `INBENTA_ENDPOINT_AUTH`: This is the endpoint to get the authentication.
- `INBENTA_ENDPOINT_API`: This is the endpoint to get the chatbot's API url.
- `INBENTA_ENDPOINT_CHATBOT_CREATE_CONVERSATION`: This is the endpoint to create a conversation with the chatbot.
- `INBENTA_ENDPOINT_CHATBOT_SEND_MESSAGE`: This is the endpoint the send a message to the chatbot.
- `INBENTA_ENDPOINT_CHATBOT_GET_HISTORY`: This is the endpoint to get the history of the conversation.
- `INBENTA_MAX_NOT_FOUND_RESULTS`: This is the maximum of consecutive "no founds" answers by the bot.
- `INBENTA_KEY_WORD`: This is the key word to print a list of pokemon locations.
- `POKEAPI_URL`: This is the url of PokeAPI.
- `POKEAPI_ENDPOINT_POKEMON`: This is the endpoint to get the pokemons.
- `POKEAPI_ENDPOINT_LOCATION`: This is the endpoint to get the pokemon locations.
- `POKEAPI_PAGINATION_LIMIT`: This is the pagination limit of result of the endpoints (default: 5).
- `POKEAPI_PAGINATION_OFFSET_RANDOM`: This is for indicating whether the offset of the pagination is random (default: true).

--- 

The server provides an API with the following endpoints:

#### `POST /api/v1/conversation/message`
This endpoint connects with the Inbenta Chatbot API (if it is not already connected or the access token is expired), creates a conversation (if it is not already created), sends the message and returns the response of Chatbot. The definition of the body for request and response of this endpoint are:

- Body for request. The `message` field is required, it should be a string and its length should be less than 255 characters.

```json
{
	"message": "string"
}
```

   - Response of the endpoint. The `response` array contains all messages returned from Chatbot API.

```json
{
	"response": [
		"string"
	]
}
```

#### `GET /api/v1/conversation/history`
 This endpoints retrieves the whole conversation with the Chatbot API (it there is an active conversation). The response of this endpoint is:

- Response of the endpoint. The content of the `history` array is provided from the Chatbot API, so it will change if the response of Chatbot API changes. The `history` array will be empty if there is no active conversation or there are not message in the conversation.

```json
{
	"history": [
		{
			"user": "string",
		    	"message": "string",
		    	"datetime": "string"
		}
	]
}
```

