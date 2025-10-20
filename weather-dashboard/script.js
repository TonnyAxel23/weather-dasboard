document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('search-btn');
    const cityInput = document.getElementById('city-input');
    
    searchBtn.addEventListener('click', fetchWeather);
    cityInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            fetchWeather();
        }
    });
});

async function fetchWeather() {
    const city = document.getElementById('city-input').value.trim();
    if (!city) {
        alert('Please enter a city name');
        return;
    }
    
    try {
        const response = await fetch(`weather-proxy.php?city=${encodeURIComponent(city)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        if (!data.city || !data.current) {
            throw new Error('Invalid data received from server');
        }
        
        displayWeather(data);
    } catch (error) {
        console.error('Error fetching weather:', error);
        alert(`Error: ${error.message}`);
    }
}

function displayWeather(data) {
    // Show weather sections
    document.getElementById('current-weather').classList.remove('hidden');
    document.getElementById('forecast').classList.remove('hidden');
    
    // Current weather
    const current = data.current;
    document.getElementById('current-city').textContent = `${data.city}, ${data.country || ''}`;
    document.getElementById('current-temp').textContent = `${Math.round(current.temp)}°C`;
    document.getElementById('current-desc').textContent = current.weather[0].description;
    document.getElementById('current-humidity').textContent = current.humidity;
    document.getElementById('current-wind').textContent = current.wind_speed;
    
    // Weather icon
    const iconCode = current.weather[0].icon;
    const iconImg = document.getElementById('current-icon');
    iconImg.src = `https://openweathermap.org/img/wn/${iconCode}@2x.png`;
    iconImg.alt = current.weather[0].main;
    
    // 5-day forecast
    const forecastContainer = document.getElementById('forecast-container');
    forecastContainer.innerHTML = '';
    
    if (data.forecast && data.forecast.length > 0) {
        data.forecast.forEach(day => {
            const date = new Date(day.dt * 1000);
            const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
            
            const card = document.createElement('div');
            card.className = 'forecast-card';
            card.innerHTML = `
                <h3>${dayName}</h3>
                <img src="https://openweathermap.org/img/wn/${day.weather[0].icon}.png" alt="${day.weather[0].main}">
                <p>${day.weather[0].description}</p>
                <div class="forecast-temp">
                    <span>${Math.round(day.temp.max)}°</span> / 
                    <span>${Math.round(day.temp.min)}°</span>
                </div>
            `;
            
            forecastContainer.appendChild(card);
        });
    }
}
