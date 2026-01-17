document.addEventListener('DOMContentLoaded', function() {

    /** ==============================
     * PAGE NAVIGATION
     * ============================== */
    const pages = document.querySelectorAll('.page');
    const navItems = document.querySelectorAll('.nav-item');
    const profileBtn = document.querySelector('.profile-btn');

    function showPage(pageId) {
        pages.forEach(p => p.classList.remove('active'));
        const page = document.getElementById(pageId + '-page') || document.getElementById(pageId);
        if(page) page.classList.add('active');
        navItems.forEach(btn => btn.classList.toggle('active', btn.dataset.page === pageId));
    }

    navItems.forEach(item => item.addEventListener('click', () => showPage(item.dataset.page)));

    if(profileBtn){
        profileBtn.addEventListener('click', function(e){
            e.preventDefault();
            showPage('profile');
        });
    }

    /** ==============================
     * SIDEBAR TOGGLE
     * ============================== */
    const toggleBtn = document.getElementById('toggleBtn');
    const sidebar = document.querySelector('.sidebar');
    if(toggleBtn && sidebar){
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
    }

    /** ==============================
     * SEARCH FORM + DATE/TIME VALIDATION
     * ============================== */
    const searchForm = document.querySelector('.booking-form');
    const searchResults = document.querySelector('.search-results');
    const searchDate = document.querySelector('input[name="date"]');
    const searchTime = document.querySelector('input[name="time"]');

    function updateSearchDateTimeLimits() {
        const now = new Date();
        if(searchDate){
            const todayStr = now.toISOString().split('T')[0];
            searchDate.min = todayStr;
        }
        if(searchTime && searchDate){
            if(searchDate.value === new Date().toISOString().split('T')[0]){
                const hours = now.getHours().toString().padStart(2,'0');
                const minutes = now.getMinutes().toString().padStart(2,'0');
                searchTime.min = `${hours}:${minutes}`;
            } else {
                searchTime.min = "00:00";
            }
        }
    }

    updateSearchDateTimeLimits();
    if(searchDate){
        searchDate.addEventListener('change', updateSearchDateTimeLimits);
    }

    if(searchForm){
        searchForm.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(searchForm);

            const selectedDate = formData.get('date');
            const selectedTime = formData.get('time');

            // ✅ Prevent past date/time submission
            const now = new Date();
            const selectedDateTime = new Date(`${selectedDate}T${selectedTime}`);
            if(selectedDateTime < now){
                alert('⚠ You cannot search for past date/time.');
                return;
            }

            fetch('search_service.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(!searchResults) return;
                searchResults.innerHTML = '';
                if(!data || data.length === 0){
                    searchResults.innerHTML = '<p>No providers found for this service/time/location.</p>';
                    return;
                }
                const grid = document.createElement('div');
                grid.classList.add('provider-grid');
                data.forEach(vendor => {
                    const card = document.createElement('div');
                    card.classList.add('provider-card');
                    card.innerHTML = `
                        <div class="provider-header">
                            <h3>${vendor.provider_name}</h3>
                            <span class="service-tag">${vendor.category}</span>
                        </div>
                        <div class="provider-rating">
                            ⭐ ${Number(vendor.avg_rating).toFixed(1)} <span class="reviews">(${vendor.total_reviews} reviews)</span>
                        </div>
                        <p class="provider-skill">${vendor.description}</p>
                        <p class="provider-location">📍 ${vendor.location}</p>
                        <p class="provider-price">₹${vendor.price}/hour</p>
                        <div class="provider-actions">
                            ${vendor.is_available ? 
                                `<button type="button" class="book-btn"
                                    data-vendor-id="${vendor.provider_id}"
                                    data-service-id="${vendor.service_id}"
                                    data-vendor-name="${vendor.provider_name}"
                                    data-service-name="${vendor.category}"
                                    data-price="${vendor.price}"
                                    data-date="${formData.get('date')}"
                                    data-time="${formData.get('time')}"
                                >📌 Book Now</button>` :
                                `<button type="button" class="book-btn disabled" disabled>❌ Not Available</button>`
                            }
                            <a href="tel:+977XXXXXXXXXX" class="call-btn">📞 Call</a>
                            <a href="https://wa.me/977XXXXXXXXXX" target="_blank" class="whatsapp-btn">💬 WhatsApp</a>
                        </div>
                    `;
                    grid.appendChild(card);
                });
                searchResults.appendChild(grid);
            })
            .catch(err => {
                console.error(err);
                if(searchResults) searchResults.innerHTML = '<p class="error">Error fetching providers.</p>';
            });
        });
    }

    /** ==============================
     * BOOK NOW BUTTON (Event Delegation)
     * ============================== */
    const bookingModal = document.getElementById('bookingModal');
    const bookingForm = document.getElementById('bookingForm');
    const modalDate = document.getElementById('modalDate');
    const modalTime = document.getElementById('modalTime');
    const closeBtn = bookingModal ? bookingModal.querySelector('.close-btn') : null;

    function openBookingModal(data){
        if(!bookingModal) return;
        document.getElementById('modalVendorId').value = data.vendorId;
        document.getElementById('modalServiceId').value = data.serviceId;
        document.getElementById('modalVendorDisplay').innerText = data.vendorName;
        document.getElementById('modalServiceDisplay').innerText = data.serviceName;
        document.getElementById('modalPriceDisplay').innerText = data.price;

        const now = new Date();
        modalDate.value = data.date;
        modalTime.value = data.time;

        // Disable past dates
        modalDate.min = now.toISOString().split('T')[0];

        // Disable past times for today
        if(modalDate.value === now.toISOString().split('T')[0]){
            const h = now.getHours().toString().padStart(2,'0');
            const m = now.getMinutes().toString().padStart(2,'0');
            modalTime.min = `${h}:${m}`;
        } else {
            modalTime.min = "00:00";
        }

        bookingModal.style.display = 'flex';
    }

    if(closeBtn){
        closeBtn.addEventListener('click', ()=> bookingModal.style.display='none');
        window.addEventListener('click', e=> {if(e.target === bookingModal) bookingModal.style.display='none';});
    }

    if(bookingForm){
        bookingForm.addEventListener('submit', function(e){
            e.preventDefault();
            const fd = new FormData(bookingForm);
            const selected = new Date(`${fd.get('date')}T${fd.get('time')}`);
            if(selected < new Date()){ alert('⚠ Cannot book past date/time'); return; }

            fetch('book_service.php', {method:'POST', body:fd})
            .then(res=>res.json())
            .then(data=>{
                if(data.success){
                    alert('✅ Booking successful!');
                    bookingModal.style.display = 'none';
                    bookingForm.reset();
                    window.location.reload();
                } else {
                    alert('❌ Booking failed: ' + data.message);
                }
            })
            .catch(err=>{
                console.error(err);
                alert('Error submitting booking.');
            });
        });
    }

    function fetchMyBookings(){
        fetch('fetch_my_bookings.php')
        .then(res=>res.text())
        .then(html=>{
            const bookingsPage = document.getElementById('bookings-page');
            if(bookingsPage){
                bookingsPage.innerHTML = html;
                showPage('bookings');
            }
        })
        .catch(err=>console.error('Error fetching bookings:', err));
    }
    const bookingsNav = document.querySelector('.nav-item[data-page="bookings"]');
    if(bookingsNav){
        bookingsNav.addEventListener('click', fetchMyBookings);
    }


// ✅ Event Delegation for dynamically added Book Now buttons
document.addEventListener('click', function(e){
    if(e.target && e.target.classList.contains('book-btn') && !e.target.classList.contains('disabled')){
        const btn = e.target;
        const data = {
            vendorId: btn.getAttribute('data-vendor-id'),
            serviceId: btn.getAttribute('data-service-id'),
            vendorName: btn.getAttribute('data-vendor-name'),
            serviceName: btn.getAttribute('data-service-name'),
            price: btn.getAttribute('data-price'),
            date: btn.getAttribute('data-date'),
            time: btn.getAttribute('data-time')
        };
        openBookingModal(data);
    }
});
const kpis = document.querySelectorAll('.kpi-value');

if (kpis.length) {
  kpis.forEach(el => {

    const originalText = el.innerText;

    // Detect decimal (for ratings)
    const isDecimal = originalText.includes('.');
    const numberMatch = originalText.match(/[\d,.]+/);

    if (!numberMatch) return;

    const end = parseFloat(numberMatch[0].replace(/,/g,''));
    let cur = 0;
    const step = end / 30;

    const timer = setInterval(() => {
      cur += step;

      if (cur >= end) {
        el.innerText = originalText.replace(numberMatch[0], isDecimal ? end.toFixed(1) : Math.round(end));
        clearInterval(timer);
      } else {
        const value = isDecimal ? cur.toFixed(1) : Math.round(cur);
        el.innerText = originalText.replace(numberMatch[0], value);
      }
    }, 20);

  });
}

// Open/close modal
window.openEditModal = function(id, rating, text) {
    document.getElementById('editReviewId').value = id;
    document.getElementById('editRating').value = rating;
    document.getElementById('editReviewText').value = text;
    document.getElementById('editReviewModal').style.display = 'block';
}

window.closeEditModal = function() {
    document.getElementById('editReviewModal').style.display = 'none';
}
// Event delegation for dynamically loaded payment buttons
document.addEventListener('click', function(e){
    const btn = e.target.closest('.pay-btn');
    if(!btn) return;

    const booking_id = btn.dataset.bookingId;
    const amount = btn.dataset.amount;
    const vendor_id = btn.dataset.vendorId;
    const service_id = btn.dataset.serviceId;

    if(!booking_id || !amount){
        alert('Invalid booking data');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'esewa_payment.php';

    [['booking_id', booking_id], ['amount', amount], ['vendor_id', vendor_id], ['service_id', service_id]].forEach(([name, value])=>{
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
});



});
