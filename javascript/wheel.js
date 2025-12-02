// --- CẤU HÌNH VÒNG QUAY ---
const sectors = [
    { color: "#FFBC03", text: "#333", label: "giảm 10%" },
    { color: "#FF5A10", text: "#fff", label: "giảm 20%" },
    { color: "#FFBC03", text: "#333", label: "Free Ship" },
    { color: "#FF5A10", text: "#fff", label: "Thêm lượt" },
    { color: "#FFBC03", text: "#333", label: "Chúc may mắn lần sau" },
    { color: "#FF5A10", text: "#fff", label: "giảm 50%" },
    { color: "#FFBC03", text: "#333", label: "Voucher 50k" },
    { color: "#FF5A10", text: "#fff", label: "Quà bí mật" },
];

const el = document.querySelector('#canvas');
const ctx = el.getContext('2d');
const btn = document.querySelector('.spin-btn');
const msg = document.querySelector('#result-msg');

const dia = ctx.canvas.width;
const rad = dia / 2;
const PI = Math.PI;
const TAU = 2 * PI;
const arc = TAU / sectors.length;

let ang = 0; // Góc hiện tại
let isSpinning = false; // Trạng thái đang quay

// --- VẼ VÒNG QUAY ---
const getIndex = () => {
    // Tính góc thực tế để biết kim đang chỉ vào đâu
    let currentAngle = ang % TAU;
    let index = Math.floor(sectors.length - (currentAngle / TAU) * sectors.length) % sectors.length;
    return index;
};

function drawSector(sector, i) {
    const ang = arc * i;
    ctx.save();
    
    // Vẽ phần rẻ quạt
    ctx.beginPath();
    ctx.fillStyle = sector.color;
    ctx.moveTo(rad, rad);
    ctx.arc(rad, rad, rad, ang, ang + arc);
    ctx.lineTo(rad, rad);
    ctx.fill();

    // Vẽ chữ
    ctx.translate(rad, rad);
    ctx.rotate(ang + arc / 2);
    ctx.textAlign = "right";
    ctx.fillStyle = sector.text;
    ctx.font = "bold 20px sans-serif";
    ctx.fillText(sector.label, rad - 10, 10);
    
    ctx.restore();
}

function rotate() {
    // Xoay canvas
    ctx.canvas.style.transform = `rotate(${ang - PI / 2}rad)`;
    
    // Cập nhật trạng thái nút
    if (!isSpinning) {
        btn.textContent = "QUAY";
        btn.style.background = "#fff";
        btn.style.color = "#1c4e80";
        btn.style.cursor = "pointer";
    } else {
        btn.textContent = "...";
        btn.style.background = "#ccc";
        btn.style.color = "#fff";
        btn.style.cursor = "not-allowed";
    }
}

// Hàm khởi tạo vẽ lần đầu
function init() {
    sectors.forEach(drawSector);
    rotate();
}

// --- LOGIC QUAY THEO THỜI GIAN (4 GIÂY) ---
function spin() {
    if (isSpinning) return;
    isSpinning = true;
    msg.textContent = "Đang quay...";

    const duration = 4000; // Tổng thời gian quay: 4000ms = 4 giây
    const startAngle = ang; // Góc bắt đầu
    
    // Tính toán góc đích đến:
    // Quay ít nhất 10 vòng (10 * TAU) + một khoảng ngẫu nhiên
    const extraSpins = 10 * TAU; 
    const randomAngle = Math.random() * TAU;
    const totalRotation = extraSpins + randomAngle;
    const endAngle = startAngle + totalRotation;

    let startTime = null;

    function animate(currentTime) {
        if (!startTime) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        
        // Tính % tiến độ (từ 0 đến 1)
        let progress = timeElapsed / duration;

        if (progress < 1) {
            // --- CÔNG THỨC QUAN TRỌNG: Easing Function ---
            // Dùng easeOutQuint: Chạy rất nhanh lúc đầu và hãm phanh cực gấp ở cuối
            // Công thức: 1 - pow(1 - x, 5)
            const ease = 1 - Math.pow(1 - progress, 5);
            
            ang = startAngle + (endAngle - startAngle) * ease;
            rotate();
            requestAnimationFrame(animate);
        } else {
            // --- KHI HOÀN THÀNH (SAU 4s) ---
            ang = endAngle; // Chốt góc cuối cùng
            isSpinning = false;
            rotate();
            
            // Xử lý kết quả
            const wonSector = sectors[getIndex()];
            msg.textContent = `Chúc mừng! Bạn nhận được: ${wonSector.label}`;
            
            // Hiệu ứng pháo hoa
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 150,
                    spread: 70,
                    origin: { y: 0.6 },
                    zIndex: 10001
                });
            }
        }
    }

    requestAnimationFrame(animate);
}

init();

// --- XỬ LÝ ĐÓNG/MỞ MODAL ---
const wheelModal = document.getElementById('wheelModal');

function openWheel() {
    wheelModal.style.display = 'flex';
}

function closeWheel() {
    wheelModal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == wheelModal) {
        wheelModal.style.display = "none";
    }
}