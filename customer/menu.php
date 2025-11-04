<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>BURGER TIME - Menu</title>
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background-color: aquamarine; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    h1 { text-align: center; text-shadow: 1px 1px #fff; }
    .layout { display: flex; gap: 30px; justify-content: center; align-items: flex-start; flex-wrap: wrap; }
    .menu { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; max-width: 900px; }
    .card { background-color: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); width: 250px; overflow: hidden; border: solid 1px black; transition: transform 0.2s; }
    .card:hover { transform: scale(1.05); }
    .card img { width: 100%; height: 160px; object-fit: cover; }
    .card-content { padding: 15px; }
    .card-content h3 { margin: 0; font-size: 1.1em; }
    .card-content .price { font-weight: bold; color: green; }
    .buy { background-color: orange; width: 100%; padding: 10px; border-radius: 25px; cursor: pointer; color: black; font-size: large; border: none; }
    .cart-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); width: 300px; position: sticky; top: 20px; }
    .cart-items { max-height: 300px; overflow-y: auto; margin-bottom: 20px; }
    .cart-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    .cart-item-info { flex-grow: 1; margin-right: 10px; }
    .cart-item-name { font-weight: bold; margin-bottom: 5px; }
    .cart-item-price { color: #666; font-size: 0.9em; }
    .cart-item-quantity { display: flex; align-items: center; margin: 5px 0; }
    .quantity-btn { width: 25px; height: 25px; border: 1px solid #ddd; background: #f5f5f5; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; }
    .quantity-value { margin: 0 10px; min-width: 20px; text-align: center; }
    .cart-item-total { font-weight: bold; text-align: right; min-width: 60px; }
    .cart-total { font-weight: bold; text-align: right; font-size: 1.2em; padding: 10px 0; border-top: 2px solid #eee; }
    .checkout-btn { background-color: orange; color: white; border: none; padding: 10px; width: 100%; border-radius: 5px; cursor: pointer; margin-top: 10px; font-weight: bold; }
    #cash-input { width: 100%; padding: 10px; margin-top: 10px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
    #cash-input:focus { outline: none; border-color: #ff9900; }
    .modal { display: none; position: fixed; z-index: 200; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: white; margin: 10% auto; padding: 20px; width: 60%; max-width: 500px; border-radius: 10px; }
    .close { float: right; cursor: pointer; font-size: 20px; }
    .receipt-item { display: flex; justify-content: space-between; margin-bottom: 5px; }
    .receipt-total { font-weight: bold; border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; }
  </style>
</head>
<body>
  <h1>OUR MENU</h1>
  <div class="layout">
    <div class="menu" id="menu-list"></div>
    <div id="menu-empty" style="display:none; text-align:center; width:100%; color:#333;">
      <p style="font-size:1.1rem;">No products available yet. Please check back later.</p>
    </div>

    <div class="cart-container">
      <h3>Your Order</h3>
      <div class="cart-items"></div>
      <div class="cart-total">
        Total: ₱<span id="cart-total">0</span>
      </div>
      <label for="cash-input">Enter Cash (₱):</label>
      <input type="number" id="cash-input" min="0" placeholder="Enter your cash here" />
      <button class="checkout-btn" id="checkout-btn">Checkout</button>
    </div>
  </div>

  <div id="receipt-modal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Receipt</h2>
      <div id="receipt-items"></div>
      <div id="receipt-total" class="receipt-total">
        Total: ₱<span id="receipt-total-amount">0</span><br>
        Change: ₱<span id="receipt-change-amount">0</span>
      </div>
      <p>Thank you for your order!</p>
    </div>
  </div>

  <script>
    $(document).ready(function () {
      let cart = [];
      let total = 0;

      function loadProducts() {
        const form = new FormData();
        form.append('action', 'list_products_public');
        fetch('../admin/api/handler.php', { method: 'POST', body: form })
          .then(r => r.json())
          .then(res => {
            if (!res.success) {
              console.error(res.message || 'Failed to load products');
              return;
            }
            const container = $('#menu-list');
            container.empty();
            const items = res.products || [];
            if (items.length === 0) {
              $('#menu-empty').show();
              // Do not return; still append default items below
            } else {
              $('#menu-empty').hide();
            }
            items.forEach(p => {
              const price = Number(p.price).toFixed(0);
              const imgSrc = p.image ? ('../upload/' + p.image) : 'https://via.placeholder.com/300x160?text=No+Image';
              const card = $(`
                <div class="card">
                  <img src="${imgSrc}" alt="${p.product_name}">
                  <div class="card-content">
                    <h3>${p.product_name}</h3>
                    <p class="price">₱${price}</p>
                    <button class="buy" data-name="${p.product_name}" data-price="${price}" data-id="${p.id}">Add to Cart</button>
                  </div>
                </div>
              `);
              container.append(card);
            });

            // Also include existing default static menu items
            const defaultItems = [
              { name: 'Cheese burger', price: 100, img: 'https://brookrest.com/wp-content/uploads/2020/05/AdobeStock_282247995-scaled.jpeg' },
              { name: 'Big mac', price: 220, img: 'https://wallpaperaccess.com/full/960318.jpg' },
              { name: 'Double Cheese Burger with vegetables', price: 190, img: 'https://img.freepik.com/premium-photo/double-cheeseburger-with-cheese-vegetables_158863-34076.jpg' },
              { name: 'Junior chicken', price: 120, img: 'https://th.bing.com/th/id/OIP.nzOw2DTZE1YhW5Kzb3fr4AAAAA?rs=1&pid=ImgDetMain' },
              { name: 'Filet-O-Fish', price: 179, img: 'https://www.shefinds.com/files/2017/11/mcdonalds-fish-filet-2.jpg' },
              { name: 'Mc Chicken', price: 100, img: 'https://th.bing.com/th/id/OIP.Qw59P5Kntp_Y0_O0dfSwvwHaJ1?w=928&h=1232&rs=1&pid=ImgDetMain' },
              { name: 'Mc Veggie', price: 120, img: 'https://th.bing.com/th/id/OIP.TDhB6Sa1ENqqVS-z0h74ZQHaEo?w=640&h=400&rs=1&pid=ImgDetMain' },
              { name: 'Double Cheese Burger', price: 180, img: 'https://offloadmedia.feverup.com/secretldn.com/wp-content/uploads/2019/08/18093813/Bundance-4.jpg' },
              { name: 'Bacon Cheese burger', price: 200, img: 'https://www.mashed.com/img/gallery/the-best-burger-in-hawaii-comes-from-an-unlikely-source/l-intro-1609119263.jpg' }
            ];
            defaultItems.forEach(d => {
              const card = $(`
                <div class="card">
                  <img src="${d.img}" alt="${d.name}">
                  <div class="card-content">
                    <h3>${d.name}</h3>
                    <p class="price">₱${Number(d.price).toFixed(0)}</p>
                    <button class="buy" data-name="${d.name}" data-price="${Number(d.price).toFixed(0)}" data-id="0">Add to Cart</button>
                  </div>
                </div>
              `);
              container.append(card);
            });
          })
          .catch(err => console.error(err));
      }
      loadProducts();

      $(document).on('click', '.buy', function () {
        const name = $(this).data('name');
        const price = parseFloat($(this).data('price'));
        const pid = parseInt($(this).data('id')) || 0;
        const existingItem = cart.find(item => item.name === name);
        if (existingItem) {
          existingItem.quantity += 1;
          existingItem.total += price;
        } else {
          cart.push({ name, price, quantity: 1, total: price, product_id: pid });
        }
        total += price;
        updateCart();
        if (window.Swal) {
          Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: name + ' added to cart', showConfirmButton: false, timer: 1200 });
        }
      });

      function updateCart() {
        $('.cart-items').empty();
        cart.forEach((item, index) => {
          $('.cart-items').append(`
            <div class="cart-item">
              <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${item.price.toFixed()} each</div>
                <div class="cart-item-quantity">
                  <button class="quantity-btn decrease" data-index="${index}">-</button>
                  <span class="quantity-value">${item.quantity}</span>
                  <button class="quantity-btn increase" data-index="${index}">+</button>
                </div>
              </div>
              <div class="cart-item-total">₱${item.total.toFixed()}</div>
            </div>
          `);
        });
        $('#cart-total').text(total.toFixed());
      }

      $(document).on('click', '.increase', function () {
        const index = $(this).data('index');
        cart[index].quantity++;
        cart[index].total += cart[index].price;
        total += cart[index].price;
        updateCart();
      });

      $(document).on('click', '.decrease', function () {
        const index = $(this).data('index');
        if (cart[index].quantity > 1) {
          cart[index].quantity--;
          cart[index].total -= cart[index].price;
          total -= cart[index].price;
        } else {
          total -= cart[index].price;
          cart.splice(index, 1);
        }
        updateCart();
      });

      $('#checkout-btn').click(async function () {
        if (cart.length === 0) {
          if (window.Swal) { Swal.fire('Error', 'Your cart is empty!', 'error'); } else { alert('Your cart is empty!'); }
          return;
        }
        const cashInput = parseFloat($('#cash-input').val());
        if (isNaN(cashInput)) {
          if (window.Swal) { Swal.fire('Error', 'Please enter a valid cash amount.', 'error'); } else { alert('Please enter a valid cash amount.'); }
          return;
        }
        if (cashInput < total) {
          if (window.Swal) { Swal.fire('Error', 'Insufficient cash! Please provide at least ₱' + total.toFixed(), 'error'); } else { alert('Insufficient cash! Please provide at least ₱' + total.toFixed()); }
          return;
        }
        const itemsPayload = cart.map(it => ({ product_id: it.product_id || 0, product_name: it.name, qty: it.quantity, price: it.price }));
        const fd = new FormData();
        fd.append('action', 'create_order_public');
        fd.append('items', JSON.stringify(itemsPayload));
        fd.append('payment_amount', String(cashInput));
        try {
          const resp = await fetch('../admin/api/handler.php', { method: 'POST', body: fd });
          const res = await resp.json();
          if (!res.success) {
            await Swal.fire('Error', res.message || 'Checkout failed', 'error');
            return;
          }
          const change = cashInput - total;
          $('#receipt-items').empty();
          cart.forEach(item => {
            $('#receipt-items').append(`
              <div class="receipt-item">
                <span>${item.name} x${item.quantity}</span>
                <span>₱${item.total.toFixed()}</span>
              </div>
            `);
          });
          $('#receipt-total-amount').text(total.toFixed());
          $('#receipt-change-amount').text(change.toFixed());
          $('#receipt-modal').show();
        } catch (e) {
          await Swal.fire('Error', 'Network error during checkout', 'error');
        }
      });

      $('.close').click(function () {
        $('#receipt-modal').hide();
        cart = [];
        total = 0;
        updateCart();
        $('#cash-input').val('');
      });

      $(window).click(function (event) {
        if (event.target.id === 'receipt-modal') {
          $('#receipt-modal').hide();
          cart = [];
          total = 0;
          updateCart();
          $('#cash-input').val('');
        }
      });
    });
  </script>
</body>
</html>

