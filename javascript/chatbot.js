// Khởi tạo logic khi DOM đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    // Tham chiếu các phần tử chính của hộp chat
    const btn = document.getElementById('chatbotBtn');
    const windowEl = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const messagesBox = document.getElementById('chatbotMessages');
    const input = document.getElementById('chatbotInput');
    const sendBtn = document.getElementById('chatbotSend');
  
    let greeted = false; // Đảm bảo chỉ gửi lời chào một lần
  
    // Toggle mở/đóng hộp chat
    btn?.addEventListener('click', () => {
      windowEl?.classList.toggle('active');
  
      if (windowEl?.classList.contains('active')) {
        if (!greeted) {
          addBotMessage('Xin chào! Tôi có thể giúp bạn tìm sách phù hợp. Hãy mô tả loại sách bạn đang tìm nhé!');
          greeted = true;
        }
        // Trễ nhẹ để focus vào ô nhập khi hộp chat mở
        setTimeout(() => input?.focus(), 200);
      }
    });
  
    // Nút đóng x trong header
    closeBtn?.addEventListener('click', () => {
      windowEl?.classList.remove('active');
    });
  
    // ----- Gửi tin nhắn tới server -----
    function sendMessage() {
      const text = input?.value.trim();
      if (!text) return; // Không gửi nếu rỗng
  
      addUserMessage(text);   // Hiển thị tin nhắn người dùng
      input.value = '';       // Reset ô nhập
  
      const typingIndicator = addBotMessage('Đang tìm kiếm...', true); // Thêm trạng thái "đang xử lý"
  
      const formData = new FormData();
      formData.append('message', text);
  
      fetch('chatbot.php', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          typingIndicator.remove(); // Xóa trạng thái
          if (data.status === 'success') {
            addBotMessage(data.message);
            if (Array.isArray(data.products) && data.products.length > 0) {
              addProductSuggestions(data.products);
            }
          } else {
            addBotMessage('Xin lỗi, tôi chưa thể xử lý yêu cầu này. Bạn thử lại nhé!');
          }
        })
        .catch(err => {
          console.error(err);
          typingIndicator.remove();
          addBotMessage('Có lỗi kết nối. Bạn thử gửi lại giúp mình nhé!');
        });
    }
  
    // Click nút gửi
    sendBtn?.addEventListener('click', sendMessage);
    // Nhấn Enter trong ô nhập cũng gửi
    input?.addEventListener('keypress', event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        sendMessage();
      }
    });
  
    // ----- Helper hiển thị tin nhắn -----
    function addUserMessage(text) {
      const el = document.createElement('div');
      el.className = 'message user';
      el.textContent = text;
      messagesBox?.appendChild(el);
      scrollToBottom();
    }
  
    function addBotMessage(text, isTyping = false) {
      const el = document.createElement('div');
      el.className = 'message bot';
      if (isTyping) el.dataset.typing = 'true';
      el.textContent = text;
      messagesBox?.appendChild(el);
      scrollToBottom();
      return el;
    }
  
    // ----- Render danh sách sách gợi ý -----
    function addProductSuggestions(products) {
      const wrapper = document.createElement('div');
      wrapper.className = 'chatbot-products';
  
      products.forEach(product => {
        const item = document.createElement('div');
        item.className = 'chatbot-product-item';
  
        item.innerHTML = `
          <img src="${product.image}" alt="${product.title}">
          <div class="chatbot-product-info">
            <strong>${product.title}</strong>
            <span class="chatbot-product-author">${product.author}</span>
            <span class="chatbot-product-price">$${parseFloat(product.price).toFixed(2)}</span>
          </div>
        `;
  
        item.addEventListener('click', () => {
          window.alert('Ban da chon: ' + product.title);
        });
  
        wrapper.appendChild(item);
      });
  
      const container = document.createElement('div');
      container.className = 'message bot';
      container.appendChild(wrapper);
      messagesBox?.appendChild(container);
      scrollToBottom();
    }
  
    // Luôn cuộn xuống cuối để thấy tin nhắn mới
    function scrollToBottom() {
      if (!messagesBox) return;
      messagesBox.scrollTop = messagesBox.scrollHeight;
    }
  });