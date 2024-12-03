const authEndPoint = '/token';
const sensorEndPoint = '/sensors';
const credentials = { username: 'ph0wn', password: 'ph0wn'}; 
const apiUrl = window.location.origin;
let token = ''
let sensorData = { 'temperature': 0, 'windDirection': 'N', 'windSpeed': 0, 'humidity': 0, 'pressure': 0 };


async function getJwtToken(authUrl, credentials) {
    try {
        const response = await fetch(authUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(credentials)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        return data.access_token; 
    } catch (error) {
        console.error('Error:', error);
    }
}

async function JwtGETRequest(url) {
    try {
        const response = await fetch(url, {
            method: 'GET', 
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (!response.ok) {
            initToken()
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        document.getElementById('temperature').innerText = data.temperature;
        document.getElementById('windDirection').innerText = data.windDirection;
        document.getElementById('windSpeed').innerText = data.windSpeed;
        document.getElementById('humidity').innerText = data.humidity;
        document.getElementById('pressure').innerText = data.pressure;
        
    } catch (error) {
        console.error('Error:', error);
    }
}





async function initToken() {
    token = await getJwtToken(apiUrl+authEndPoint, credentials);
}


initToken();


setInterval(() => {
    if (token !== ''){
        JwtGETRequest(apiUrl+sensorEndPoint);
    }
}, 5000);



