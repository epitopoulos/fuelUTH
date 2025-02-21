let gasStations = [];
let priceData = [];
let markers = [];
let gasStationsCount;
let fuelMinMaxAvg = [];
let priceList = [];
let owner_or_customer = '';
let ownerGasStationID;
let ownerOrdersList = [];
let ownerPriceList = [];
let customerOrdersList = [];

function priceDataFetcher(queryParam = '') {

  hideContent();
  
  return $.ajax({
    url: 'http://localhost/api/pricedata' + queryParam,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        priceData = data;
        const gasStationIds = priceData.map(item => item.gasStationID)
        gasStationsFetcher(gasStationIds);
        gasStationsCountFetcher(gasStationIds);
    },
    error: function(xhr, status, error) {
        console.error('Error fetching price data:', error);
    }
  });
}

function gasStationsFetcher(gasStationIds) {
    const idsQuery = gasStationIds.join(',');
    const url = `http://localhost/api/gasstations?gasStationID=${idsQuery}`;

    return $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      success: function(data) {
          gasStations = data;
      },
      error: function(xhr, status, error) {
        console.error('Error fetching gas stations:', error);
    }
    })
    .then(function() {
      addMarkerInfo();
    })
    .then(function() {
      displayContent();
    })
  }

function gasStationsCountFetcher(gasStationIds) {
  const idsQuery = gasStationIds.join(',');
  const url = `http://localhost/api/gasstations/count?gasStationID=${idsQuery}`;

  return $.ajax({
    url: url,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        gasStationsCount = data;
    },
    error: function(xhr, status, error) {
      console.error('Error fetching gas stations count:', error);
  }
  })
  .then(function() {
    updateNavbarInfo();
  })
}

function fuelMinMaxAvgFetcher(queryParam = '') {

  return $.ajax({
    url: 'http://localhost/api/pricedata/minmaxavg' + queryParam,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        fuelMinMaxAvg = data;
    },
    error: function(xhr, status, error) {
      console.error('Error fetching price data minmaxavg:', error);
  }
  })
}

function priceListFetcher($gasStationID) {
  const url = `http://localhost/api/pricedata/pricelist?gasStationID=${$gasStationID}`;

  return $.ajax({
    url: url,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        priceList = data;
    },
    error: function(xhr, status, error) {
      console.error('Error fetching price list:', error);
  }
  })
}

function ownerPriceListFetcher($gasStationID) {
  const url = `http://localhost/api/pricedata/pricelist?gasStationID=${$gasStationID}`;

  return $.ajax({
    url: url,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        ownerPriceList = data;
    },
    error: function(xhr, status, error) {
      console.error('Error fetching price list:', error);
  }
  })
}


function checkIfOwner($username) {
  const url = `http://localhost/api/gasstations/owners/${$username}`;

  return $.ajax({
    url: url,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
        if (data.owner_or_customer === 'owner') {
          owner_or_customer = 'owner';
          ownerGasStationID = data.gasStationID;
          ownerOrdersFetcher(ownerGasStationID);
          ownerPriceListFetcher(ownerGasStationID);
        } else if (data.owner_or_customer === 'customer'){
          owner_or_customer = 'customer';
          customerOrdersFetcher($username);
        }

        updateNavbar();
    },
    error: function(xhr, status, error) {
      console.error('Error checking if owner:', error);
  } 
  })
}


function loginTokenFetcher(username_or_email, password) {
  fetch('http://localhost/api/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        username_or_email: username_or_email,
        password: password
    })
})
.then(response => response.json())
.then(data => {
    if (data.token) {
        localStorage.setItem('token', data.token);
        localStorage.setItem('username', data.username);
        checkIfOwner(data.username);
        $('#loginModal').modal('toggle');
    } else {
        displayError('Λάθος στοιχεία', 'loginMessages');
        highlightErrorFields();
    }
})
.catch(error => {
  console.error('Error:', error);
  displayError('Αποτυχής σύνδεση', 'loginMessages');
});
}

function placeOrder(productID, quantity) {
  const token = localStorage.getItem('token');

  if (token && isTokenExpired(token)) {
    displayError('Session expired. Please log in again.', 'orderMessages');
    return;
  }
  
  let timestamp = Math.floor(Date.now() / 1000);

  fetch('http://localhost/api/orders', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
          productID: productID,
          quantity: quantity,
          when: timestamp
      })
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Αποτυχής επικοινωνία με τον server');
      }
      return response.json();
  })
  .then(data => {
      displaySuccess('Επιτυχής ολοκλήρωση παραγγελίας', 'orderMessages');
      customerOrdersFetcher(localStorage.getItem('username'));
  })
  .catch(error => {
      displayError('Αποτυχής ολοκλήρωση παραγγελίας', 'orderMessages');
  });
}

function ownerOrdersFetcher(gasStationID) {
  const token = localStorage.getItem('token');

  fetch(`http://localhost/api/orders/owner/${gasStationID}`, {
      method: 'GET',
      headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
      }
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Αποτυχής επικοινωνία με τον server');
      }
      return response.json();
  })
  .then(data => {
      ownerOrdersList = data;
  });
}

function customerOrdersFetcher(username) {
  const token = localStorage.getItem('token');
  fetch(`http://localhost/api/orders/customer/${username}`, {
      method: 'GET',
      headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
      }
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Αποτυχής επικοινωνία με τον server');
      }
      return response.json();
  })
  .then(data => {
      customerOrdersList = data;
  });
}

function checkAndDisplayDefaultMessageOwner() {
  const ownerOrdersContainer = document.getElementById('ownerOrdersContainer');
  if (ownerOrdersContainer.children.length === 0) {
    const defaultMessageOwner = document.createElement('div');
    defaultMessageOwner.setAttribute('id', 'defaultMessageOwner');
    defaultMessageOwner.textContent = 'Δεν έχετε εισερχόμενες παραγγελίες';
    ownerOrdersContainer.appendChild(defaultMessageOwner);
  } else {
    const existingMessageOwner = document.getElementById('defaultMessageOwner');
    if (existingMessageOwner) {
      ownerOrdersContainer.removeChild(existingMessageOwner);
    }
  }
}

function checkAndDisplayDefaultMessageCustomer() {
  const customerOrdersContainer = document.getElementById('customerOrdersContainer');
  if (customerOrdersContainer.children.length === 0) {
    const defaultMessageCustomer = document.createElement('div');
    defaultMessageCustomer.setAttribute('id', 'defaultMessageCustomer');
    defaultMessageCustomer.textContent = 'Δεν έχετε εκκρεμής παραγγελίες';
    customerOrdersContainer.appendChild(defaultMessageCustomer);
  } else {
    const existingMessageCustomer = document.getElementById('defaultMessageCustomer');
    if (existingMessageCustomer) {
      customerOrdersContainer.removeChild(existingMessageCustomer);
    }
  }
}

function updatePrice(productID, newPrice) {
  const token = localStorage.getItem('token');

  if (token && isTokenExpired(token)) {
    displayError('Session expired. Please log in again.', 'priceMessages');
    return;
  }

  let timestamp = Math.floor(Date.now() / 1000);

  fetch(`http://localhost/api/pricedata/update/${productID}`, {
      method: 'PUT',
      headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({
          newPrice: newPrice,
          dateUpdated: timestamp
      })
  })
  .then(response => {
      if (!response.ok) {
          throw new Error('Αποτυχής επικοινωνία με τον server');
      }
      return response.json();
  })
  .then(data => {
    displaySuccess('Επιτυχής αλλαγή τιμής', 'pricesMessages');
    ownerPriceListFetcher(ownerGasStationID);
    document.getElementById('fuelTypeSelect').selectedIndex = 0;
    document.getElementById('oldPriceDisplay').value = '';
    document.getElementById('newPriceInput').hidden = true;
  })
  .catch(error => {
    displayError('Αποτυχής αλλαγή τιμής', 'orderMessages');
  });
}

function acceptOrder(orderId) {
  const url = `http://localhost/api/orders/${orderId}/accept`;

  const token = localStorage.getItem('token');

  fetch(url, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    if (data.success = true) {
      displaySuccess('Επιτυχής αποδοχή παραγγελίας', 'ownerOrdersMessages');
      ownerOrdersFetcher(ownerGasStationID);
      document.getElementById(`ownerOrder-${orderId}`).remove();
      checkAndDisplayDefaultMessageOwner();
    } else if (data.success = false) {
      displayError('Αποτυχία αποδοχής παραγγελίας', 'ownerOrdersMessages');
    }
  })
  .catch(error => {
    console.error('Error accepting order:', error);
    displayError('Αποτυχία αποδοχής παραγγελίας', 'ownerOrdersMessages');
  });
}

function deleteOrder(orderId) {
  const url = `http://localhost/api/orders/${orderId}/delete`;

  const token = localStorage.getItem('token');

  fetch(url, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    if (owner_or_customer === 'owner') {
      if (data.success = true) {
        displaySuccess('Επιτυχής ακύρωση παραγγελίας', 'ownerOrdersMessages');
        ownerOrdersFetcher(ownerGasStationID);
        if (document.getElementById(`ownerOrder-${orderId}`)) {
          document.getElementById(`ownerOrder-${orderId}`).remove();
        }
        checkAndDisplayDefaultMessageOwner();
      } else if (data.success = false) {
        displayError('Αποτυχία ακύρωσης παραγγελίας', 'ownerOrdersMessages');
      }
    } else if (owner_or_customer === 'customer') {
      if (data.success = true) {
        displaySuccess('Επιτυχής ακύρωση παραγγελίας', 'customerOrdersMessages');
        customerOrdersFetcher(localStorage.getItem('username'));
        if (document.getElementById(`customerOrder-${orderId}`)) {
          console.log(`customerOrder-${orderId}`);
          document.getElementById(`customerOrder-${orderId}`).remove();
        }
        checkAndDisplayDefaultMessageCustomer();
      } else if (data.success = false) {
        displayError('Αποτυχία ακύρωσης παραγγελίας', 'customerOrdersMessages');
      }
    }
  })
  .catch(error => {
    console.error('Error deleting order:', error);
    if (owner_or_customer === 'owner') {
      displayError('Αποτυχία ακύρωσης παραγγελίας', 'ownerOrdersMessages');
    } else if (owner_or_customer === 'customer') {
      displayError('Αποτυχία ακύρωσης παραγγελίας', 'customerOrdersMessages');
    }  
  });
}

function isTokenExpired(token) {
  const payloadBase64 = token.split('.')[1];
  const decodedPayload = JSON.parse(atob(payloadBase64));
  
  const currentTime = Date.now() / 1000;
  const expTime = decodedPayload.exp;
  
  return currentTime > expTime;
}



document.addEventListener('DOMContentLoaded', function() {

  if (localStorage.getItem('username') !== null) {
    checkIfOwner(localStorage.getItem('username'));
  }
  
  updateNavbar();

  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', function(event) {
      event.preventDefault();

      const loginMessages = document.getElementById('loginMessages');
      loginMessages.innerHTML = '';
      removeHighlight();

      const usernameOrEmail = document.getElementById('usernameOrEmail').value;
      const password = document.getElementById('password').value;
      loginTokenFetcher(usernameOrEmail, password);
  });

  const ownerPricesForm = document.getElementById('ownerPricesForm');
  ownerPricesForm.addEventListener('submit', function(event) {
      event.preventDefault();

      const pricesMessages = document.getElementById('pricesMessages');
      pricesMessages.innerHTML = '';

      const productID = document.getElementById('productID').value;
      const newPrice = document.getElementById('newPriceInput').value;

      if (newPrice>0 && (newPrice.toString().split('.')[1] || '').length <= 3) {
        updatePrice(productID, newPrice);
      } else {
        displayError('Η τιμή δεν μπορεί να είναι μηδενική', 'pricesMessages');
      }

      ownerPriceListFetcher(ownerGasStationID);
  });

  const orderForm = document.getElementById('orderForm');
  orderForm.addEventListener('submit', function(event) {
      event.preventDefault();

      const orderMessages = document.getElementById('orderMessages');
      orderMessages.innerHTML = '';

      const productID = document.getElementById('selectProduct').value;
      const quantity = document.getElementById('quantity').value;

      if (quantity == 0) {
        displayError('Η ποσότητα δεν μπορεί να είναι μηδενική', 'orderMessages');
        return;
      }

      placeOrder(productID, quantity);
  });

  const ownerOrdersContainer = document.getElementById('ownerOrdersContainer');
  ownerOrdersContainer.addEventListener('click', function(e) {
    if (e.target && e.target.matches('.accept-btn')) {
        document.getElementById('ownerOrdersMessages').innerHTML = '';
        const orderId = e.target.getAttribute('data-order-id');
        acceptOrder(orderId)
    } else if (e.target && e.target.matches('.cancel-btn')) {
        document.getElementById('ownerOrdersMessages').innerHTML = '';
        const orderId = e.target.getAttribute('data-order-id');
        deleteOrder(orderId)
    }
  });

  const customerOrdersContainer = document.getElementById('customerOrdersContainer');
  customerOrdersContainer.addEventListener('click', function(e) {
    if (e.target && e.target.matches('.cancel-btn')) {
        const orderId = e.target.getAttribute('data-order-id');
        deleteOrder(orderId)
    }
  });

});

let map;
const { Map } = await google.maps.importLibrary("maps");

function initMap() {

  map = new Map(document.getElementById("map"), {
    zoom: 9,
    center: new google.maps.LatLng(39.63833574257503, 22.416003515949026),
    mapId: "DEMO_MAP_ID",
  });
}

function updateMarkers(queryParam) {

  priceDataFetcher(queryParam);

}

var InforObj = [];

async function addMarkerInfo() {

    clearMarkers();

  for (var i = 0; i < gasStations.length; i++) {
    
    var contentString = '<div id="content">' + 
    `<h4 id=firstHeading>${gasStations[i].gasStationOwner}` +
    `<h5 id=secondHeading>${gasStations[i].gasStationAddress}` +
    `<h6 id=thirdHeading>${priceData[i].fuelName}: ${priceData[i].fuelPrice}€</h6>` +
    '<div id="bodyContent">' +
    `<p><a class="btn btn-primary priceListBtn" type="button" data-bs-toggle="offcanvas" data-bs-target="#priceList" data-gas-station-id=${gasStations[i].gasStationID}>Τιμοκατάλογος</a></p>` +
    "</div>" +
    "</div>";

    const fuelCompImg = `logos/${gasStations[i].fuelCompID}.png`;

    const marker = new google.maps.Marker({
      position: new google.maps.LatLng(gasStations[i].gasStationLat, gasStations[i].gasStationLong),
      map: map,
      icon: fuelCompImg,
    });


    markers.push(marker);

    const infowindow = new google.maps.InfoWindow({
        content: contentString
    });

    (function(marker, infowindow, gasStation) {
      marker.addListener('click', function () {
        closeOtherInfo();
        infowindow.open(map, marker);
        InforObj[0] = infowindow;
      });

      $(document).on('click', '.priceListBtn', function() {
        const selectedGasStationID = $(this).data('gas-station-id');
        if (selectedGasStationID === gasStation.gasStationID) {
            priceListFetcher(selectedGasStationID)
            .then(function() {
              const phoneNumberHTML = gasStation.phone1 ? `<p><b>Τηλέφωνο: ${gasStation.phone1}</b></p>` : '';
              document.getElementById('priceListHeader').innerHTML = `<h5>${gasStation.gasStationOwner}</h5><button class="close-btn" data-bs-toggle="offcanvas">×</button>`;
              document.getElementById('priceListSubHeader').innerHTML = `<h6>${gasStation.gasStationAddress}</h6>${phoneNumberHTML}`;
              
              const priceListBody = document.getElementById('priceListBody');
              document.getElementById('priceListBody').innerHTML = '';

              for(var i = 0; i < priceList.length; i++) {
                const premiumTag = priceList[i].isPremium ? " (Premium)" : "";
                priceListBody.insertAdjacentHTML('beforeend', `<p><b>${priceList[i].fuelName}${premiumTag}:</b> ${priceList[i].fuelPrice}€</p>`);
              }

              if (owner_or_customer === 'customer') {
                priceListBody.insertAdjacentHTML('beforeend', '<button type="button" class="btn btn-primary orderButton" data-bs-toggle="modal" data-bs-target="#orderModal">Παραγγελία</button>');
              }

            })
            .catch(function(error) {
                console.error('Error fetching price list:', error);
            });
        }
    });

      $(document).on('click', '.orderButton', function() {
        const selectProduct = document.getElementById('selectProduct');

        while(selectProduct.options.length > 1) {
        selectProduct.remove(1);
      }

      for(var i = 0; i < priceList.length; i++) {
        const option = document.createElement('option');
        option.value = priceList[i].productID;
        option.textContent = priceList[i].fuelName;
        selectProduct.appendChild(option);
      }

      const quantity = document.getElementById('quantity');
      quantity.value = null;

      const orderMessages = document.getElementById('orderMessages');
      orderMessages.innerHTML = '';

    });

    })(marker, infowindow, gasStations[i]);

  }

}

function clearMarkers() {
    for (let i = 0; i < markers.length; i++) {
      markers[i].setMap(null);
    }
    markers = [];
  }

function closeOtherInfo() {
  if (InforObj.length > 0) {
      InforObj[0].set("marker", null);
      InforObj[0].close();
      InforObj.length = 0;
  }
}

$(document).ready(function() {
    $('#Amolivdi95').on('click', function() {
        updateMarkers('?fuelTypeID=1');
        fuelMinMaxAvgFetcher('?fuelTypeID=1');
        setActiveItem($(this));
    });

    $('#Amolivdi100').on('click', function() {
        updateMarkers('?fuelTypeID=2');
        fuelMinMaxAvgFetcher('?fuelTypeID=2');
        setActiveItem($(this));
    });

    $('#Super').on('click', function() {
        updateMarkers('?fuelTypeID=3');
        fuelMinMaxAvgFetcher('?fuelTypeID=3');
        setActiveItem($(this));
    });

    $('#DieselKinisis').on('click', function() {
        updateMarkers('?fuelTypeID=4');
        fuelMinMaxAvgFetcher('?fuelTypeID=4');
        setActiveItem($(this));
    });

    $('#DieselThermansis').on('click', function() {
        updateMarkers('?fuelTypeID=5');
        fuelMinMaxAvgFetcher('?fuelTypeID=5');
        setActiveItem($(this));
    });

    $('#YgraerioKinisis').on('click', function() {
        updateMarkers('?fuelTypeID=6');
        fuelMinMaxAvgFetcher('?fuelTypeID=6');
        setActiveItem($(this));
    });

    $('#DieselThermansisKO').on('click', function() {
        updateMarkers('?fuelTypeID=7');
        fuelMinMaxAvgFetcher('?fuelTypeID=7');
        setActiveItem($(this));
    });
    initMap();

    $('#Amolivdi95').trigger('click');

    function setActiveItem(item) {
        $('.dropdown-item').removeClass('active');
        item.addClass('active');
        $('#navbarDropdown1').text(item.text());
    }
});

$(document).on('click', '#ownerOrdersButton', function() {
  const ownerOrdersContainer = document.getElementById('ownerOrdersContainer');
  ownerOrdersContainer.innerHTML = '';
  ownerOrdersMessages.innerHTML = '';
  for(var i = 0; i < ownerOrdersList.length; i++) {
      const orderCard = document.createElement('div');
      orderCard.classList.add('card', 'mb-3');
      orderCard.id = `ownerOrder-${ownerOrdersList[i].orderId}`;
      orderCard.innerHTML = `
          <div class="row g-0">
              <div class="col-md-3 bg-primary text-white d-flex align-items-center justify-content-center" style="padding: 20px;">
                  <div class="card-body"></div>
              </div>
              <div class="col-md-9">
                  <div class="card-body">
                      <p class="card-text"><strong>Προϊόν:</strong> ${ownerOrdersList[i].fuelName}</p>
                      <p class="card-text"><strong>Χρήστης:</strong> ${ownerOrdersList[i].username}</p>
                      <p class="card-text"><strong>Ποσότητα:</strong> ${ownerOrdersList[i].quantity} λίτρα</p>
                      <p class="card-text"><strong>Ημερομηνία:</strong> ${new Date(ownerOrdersList[i].when).toLocaleString()}</p>
                      <button class="btn accept-btn" data-order-id="${ownerOrdersList[i].orderId}" style="background-color: #f0f0f0; color: #333; margin-right: 10px;">Αποδοχή</button>
                      <button class="btn cancel-btn" data-order-id="${ownerOrdersList[i].orderId}" style="background-color: #f0f0f0; color: #333;">Ακύρωση</button>
                  </div>
              </div>
          </div>
      `;
      ownerOrdersContainer.appendChild(orderCard);
  }
  checkAndDisplayDefaultMessageOwner();
});

$(document).on('click', '#customerOrdersButton', function() {
  const customerOrdersContainer = document.getElementById('customerOrdersContainer');
  customerOrdersContainer.innerHTML = '';
  customerOrdersMessages.innerHTML = '';
  for(var i = 0; i < customerOrdersList.length; i++) {
      const orderCard = document.createElement('div');
      orderCard.classList.add('card', 'mb-3');
      orderCard.id = `customerOrder-${customerOrdersList[i].orderId}`;
      orderCard.innerHTML = `
          <div class="row g-0">
              <div class="col-md-3 bg-primary text-white d-flex align-items-center justify-content-center" style="padding: 20px;">
                  <div class="card-body">
                      <!-- Moved buttons here for better integration -->
                  </div>
              </div>
              <div class="col-md-9">
                  <div class="card-body">
                      <p class="card-text"><strong>Προϊόν:</strong> ${customerOrdersList[i].fuelName}</p>
                      <p class="card-text"><strong>Ποσότητα:</strong> ${customerOrdersList[i].quantity} λίτρα</p>
                      <p class="card-text"><strong>Ημερομηνία:</strong> ${new Date(customerOrdersList[i].when).toLocaleString()}</p>
                      <p class="card-text"><strong>Ιδιοκτήτης</strong> ${customerOrdersList[i].gasStationOwner}</p>
                      <p class="card-text"><strong>Διεύθυνση:</strong> ${customerOrdersList[i].gasStationAddress}</p>
                      <button class="btn cancel-btn" data-order-id="${customerOrdersList[i].orderId}" style="background-color: #f0f0f0; color: #333;">Ακύρωση</button>
                  </div>
              </div>
          </div>
      `;
      customerOrdersContainer.appendChild(orderCard);
  }
  checkAndDisplayDefaultMessageCustomer();
});

$(document).on('click', '#ownerPricesButton', function() {
  const ownerPricesContainer = document.getElementById('ownerPricesContainer');
  ownerPricesContainer.innerHTML = '';
  ownerPricesContainer.fuelSelectDropdown = '';

  const pricesMessages = document.getElementById('pricesMessages');
  pricesMessages.innerHTML = '';

  const fuelSelectDropdown = document.createElement('select');
  fuelSelectDropdown.classList.add('form-control', 'mb-3');
  fuelSelectDropdown.id = 'fuelTypeSelect';
  const defaultOption = document.createElement('option');
  defaultOption.textContent = 'Επιλέξτε καύσιμο';
  fuelSelectDropdown.appendChild(defaultOption);

  ownerPriceList.forEach((fuel, index) => {
    const option = document.createElement('option');
    option.value = index;
    option.textContent = fuel.fuelName;
    fuelSelectDropdown.appendChild(option);
  });

  ownerPricesContainer.appendChild(fuelSelectDropdown);

  const oldPriceDisplay = document.createElement('input');
  oldPriceDisplay.type = 'text';
  oldPriceDisplay.classList.add('form-control', 'mb-2');
  oldPriceDisplay.disabled = true;
  oldPriceDisplay.id = 'oldPriceDisplay';
  ownerPricesContainer.appendChild(oldPriceDisplay);

  const newPriceInput = document.createElement('input');
  newPriceInput.type = 'number';
  newPriceInput.classList.add('form-control', 'mb-2');
  newPriceInput.placeholder = 'Νέα τιμή (ανά λίτρο)';
  newPriceInput.step = '0.001';
  newPriceInput.id = 'newPriceInput';
  ownerPricesContainer.appendChild(newPriceInput);

  fuelSelectDropdown.addEventListener('change', function() {
    const selectedIndex = this.value;
    if (selectedIndex) {
      const selectedFuel = ownerPriceList[selectedIndex];
      oldPriceDisplay.value = `${selectedFuel.fuelPrice}€`;
      newPriceInput.style.display = '';
      this.children[0].disabled = true;
      document.getElementById('productID').value = selectedFuel.productID;
    } else {
      oldPriceDisplay.value = '';
      newPriceInput.style.display = 'none';
    }
  });

  newPriceInput.style.display = 'none';
});

const loader = document.getElementById('loader')

function displayContent() {
  loader.style.display = 'none';
}

function hideContent() {
  loader.style.display = 'block';
}

function updateNavbarInfo() {
  document.getElementById('navbarInfo').innerHTML = `<p><b>Πρατήρια:</b> ${gasStationsCount.count}</p><p><b>Min:</b> ${fuelMinMaxAvg.min}€</p><p><b>Max:</b> ${fuelMinMaxAvg.max}€</p><p><b>Avg:</b> ${fuelMinMaxAvg.avg}€</p>`;
}

function displayError(errorMessage, elementId) {
  const errorElement = document.createElement('div');
  errorElement.classList.add('alert', 'alert-danger');
  errorElement.textContent = errorMessage;

  document.getElementById(elementId).appendChild(errorElement);
}

function displaySuccess(successMessage, elementId) {
  const errorElement = document.createElement('div');
  errorElement.classList.add('alert', 'alert-success');
  errorElement.textContent = successMessage;

  document.getElementById(elementId).appendChild(errorElement);
}

function highlightErrorFields() {
  document.getElementById('usernameOrEmail').classList.add('is-invalid');
  document.getElementById('password').classList.add('is-invalid');
}

function removeHighlight() {
  document.getElementById('usernameOrEmail').classList.remove('is-invalid');
  document.getElementById('password').classList.remove('is-invalid');
}

function updateNavbar() {
  const token = localStorage.getItem('token');
  const username = localStorage.getItem('username');
  const userNav = document.getElementById('userNav');
  userNav.innerHTML = '';

  if (token) {

    if (owner_or_customer === 'owner') {
      const ownerOrders = document.createElement('li');
      ownerOrders.className = 'nav-item';
      ownerOrders.innerHTML = '<a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#ownerOrdersModal" id="ownerOrdersButton">Εισερχόμενες Παραγγελίες</a>';
      userNav.appendChild(ownerOrders);

      const ownerPrices = document.createElement('li');
      ownerPrices.className = 'nav-item';
      ownerPrices.innerHTML = '<a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#ownerPricesModal" id="ownerPricesButton">Τιμές Καυσίμων</a>';
      userNav.appendChild(ownerPrices);

    } else if (owner_or_customer === 'customer'){
      const customerOrders = document.createElement('li');
      customerOrders.className = 'nav-item';
      customerOrders.innerHTML = '<a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#customerOrdersModal" id="customerOrdersButton">Οι Παραγγελίες μου</a>';
      userNav.appendChild(customerOrders);
    }

      const logoutLink = document.createElement('li');
      logoutLink.className = 'nav-item';
      logoutLink.innerHTML = '<a class="nav-link" href="#" id="logoutLink">Logout</a>';
      userNav.appendChild(logoutLink);

      document.getElementById('logoutLink').addEventListener('click', function() {
        localStorage.removeItem('token');
        localStorage.removeItem('username');
        owner_or_customer = '';
        ownerGasStationID = null;
        updateNavbar();
      });

      const welcomeMessage = document.createElement('span');
      welcomeMessage.className = 'navbar-text';
      welcomeMessage.innerHTML = `<span class="navbar-text">Καλωσόρισες, ${username}</span>`;
      userNav.appendChild(welcomeMessage);
  } else {
      const loginLink = document.createElement('li');
      loginLink.className = 'navbar-item';
      loginLink.innerHTML = '<a class="nav-link" href="#" id="loginLink" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>'
      userNav.appendChild(loginLink);
  }
}