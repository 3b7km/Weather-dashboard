// API Configuration - Use current domain dynamically
const API_BASE_URL = (() => {
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;
    
    // Get the directory path (remove file name if present)
    let path = pathname.substring(0, pathname.lastIndexOf('/'));
    
    return `${protocol}//${host}${path}`;
})(); 

// State Management
let currentWeatherData = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    loadSearchHistory();
    
    // Form submission
    document.getElementById('searchForm').addEventListener('submit', handleSearch);
    
    // Clear button
    document.getElementById('clearBtn').addEventListener('click', clearHistory);
});


async function handleSearch(e) {
    e.preventDefault();
    
    const cityInput = document.getElementById('cityInput');
    const city = cityInput.value.trim();
    // if statements 
    if (!city) {
        showError('Please enter a city name');
        return;
    }
    
    // Show loading state
    showLoading();
    hideError();
    
    try {
        const response = await fetch(`${API_BASE_URL}/weather_api.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ city })
        });
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'Failed to fetch weather data');
        }
        
        if (result.success && result.data) {
            currentWeatherData = result.data;
            displayWeatherData(result.data);
            loadSearchHistory(); // Refresh history
            cityInput.value = ''; // Clear input search after the result shows
        } else {
            throw new Error('Invalid response format');
        }
        
    } catch (error) {
        console.error('Search error:', error);
        showError(error.message);
        showEmptyState();
    } finally {
        hideLoading();
    }
}

//update the weather data on the card
function displayWeatherData(data) {
    // Hide empty state
    document.getElementById('emptyState').classList.add('hidden');
    
    // Show weather card
    const weatherCard = document.getElementById('weatherCard');
    weatherCard.classList.remove('hidden');
    
    // Update city and country
    document.getElementById('cityName').textContent = data.city;
    document.getElementById('countryName').textContent = data.country;
    
    // Update main weather info
    document.getElementById('temperature').textContent = data.temperature;
    document.getElementById('description').textContent = data.description;
    document.getElementById('feelsLike').textContent = data.feelsLike;
    
    // Update weather details
    document.getElementById('humidity').textContent = data.humidity;
    document.getElementById('windSpeed').textContent = data.windSpeed;
    document.getElementById('visibility').textContent = data.visibility;
    document.getElementById('pressure').textContent = data.pressure;
    
    // Update weather icon based on weather condition
    updateWeatherIcon(data.weatherMain);
}


//Update weather icon based on condition
function updateWeatherIcon(weatherMain) {
    const iconElement = document.getElementById('weatherIcon');
    
    // Icon paths for different weather conditions
    const icons = {
        'Clear': 'M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.636l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z',
        'Clouds': 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
        'Rain': 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
        'Drizzle': 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
        'Thunderstorm': 'M13 10V3L4 14h7v7l9-11h-7z',
        'Snow': 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12',
        'Mist': 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z',
        'Fog': 'M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z'
    };
    
    const iconPath = icons[weatherMain] || icons['Clear'];
    iconElement.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>`;
    
    // Update color based on weather
    const colors = {
        'Clear': '#fbbf24',
        'Clouds': '#9ca3af',
        'Rain': '#3b82f6',
        'Drizzle': '#3b82f6',
        'Thunderstorm': '#6366f1',
        'Snow': '#dbeafe',
        'Mist': '#94a3b8',
        'Fog': '#94a3b8'
    };
    
    iconElement.style.color = colors[weatherMain] || colors['Clear'];
}


// Load and display search history

async function loadSearchHistory() {
    try {
        const response = await fetch(`${API_BASE_URL}/history_api.php?limit=10`);
        const result = await response.json();
        
        if (result.success && result.data) {
            displaySearchHistory(result.data);
        }
    } catch (error) {
        console.error('Failed to load history:', error);
    }
}


//Display search history in the sidebar

function displaySearchHistory(searches) {
    const container = document.getElementById('searchesContainer');
    const emptyHistory = document.getElementById('emptyHistory');
    
    if (searches.length === 0) {
        container.innerHTML = '';
        emptyHistory.style.display = 'block';
        return;
    }
    
    emptyHistory.style.display = 'none';
    
    container.innerHTML = searches.map(search => `
        <button class="search-item" onclick="searchFromHistory('${search.city}')">
            <div class="search-item-city">
                <span>${search.city}</span>
                <span class="search-item-country">${search.country}</span>
            </div>
            <div class="search-item-time">
                <svg class="time-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
                ${search.timeAgo}
            </div>
        </button>
    `).join('');
}

// Search from history item
function searchFromHistory(city) {
    document.getElementById('cityInput').value = city;
    document.getElementById('searchForm').dispatchEvent(new Event('submit'));
}

// Clear search history
async function clearHistory() {
    if (!confirm('Are you sure you want to clear all search history?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/history_api.php`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadSearchHistory();
        } else {
            showError('Failed to clear history');
        }
    } catch (error) {
        console.error('Clear history error:', error);
        showError('Failed to clear history');
    }
}

// Show loading state
function showLoading() {
    document.getElementById('loadingState').classList.remove('hidden');
    document.getElementById('weatherCard').classList.add('hidden');
    document.getElementById('emptyState').classList.add('hidden');
}

// Hide loading state
function hideLoading() {
    document.getElementById('loadingState').classList.add('hidden');
}


//Show empty state

function showEmptyState() {
    document.getElementById('emptyState').classList.remove('hidden');
    document.getElementById('weatherCard').classList.add('hidden');
}

 //Show error message

function showError(message) {
    const errorAlert = document.getElementById('errorAlert');
    document.getElementById('errorMessage').textContent = message;
    errorAlert.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        hideError();
    }, 5000);
}


function hideError() {
    document.getElementById('errorAlert').classList.add('hidden');
}