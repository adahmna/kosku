import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import {
    createIcons,
    AlertCircle,
    BarChart3,
    Bed,
    CalendarDays,
    CheckCircle2,
    CreditCard,
    Crown,
    DoorClosed,
    DoorOpen,
    Download,
    Eye,
    FileText,
    Home,
    Inbox,
    LayoutDashboard,
    Lock,
    LogOut,
    Mail,
    Megaphone,
    Menu,
    Pencil,
    Plus,
    Save,
    Search,
    Settings,
    Shield,
    Snowflake,
    Trash2,
    TrendingUp,
    Upload,
    User,
    UserCircle,
    Users,
    Wallet,
    Wifi,
    Wrench,
    X,
    Zap,
} from 'lucide';

const icons = {
    AlertCircle,
    BarChart3,
    Bed,
    CalendarDays,
    CheckCircle2,
    CreditCard,
    Crown,
    DoorClosed,
    DoorOpen,
    Download,
    Eye,
    FileText,
    Home,
    Inbox,
    LayoutDashboard,
    Lock,
    LogOut,
    Mail,
    Megaphone,
    Menu,
    Pencil,
    Plus,
    Save,
    Search,
    Settings,
    Shield,
    Snowflake,
    Trash2,
    TrendingUp,
    Upload,
    User,
    UserCircle,
    Users,
    Wallet,
    Wifi,
    Wrench,
    X,
    Zap,
};

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('confirmDialog', () => ({
    open: false,
    action: '',
    method: 'DELETE',
    title: '',
    message: '',
    confirmLabel: 'Hapus',
    danger: true,
    ask(detail) {
        this.action = detail.action || '';
        this.method = detail.method || 'DELETE';
        this.title = detail.title || 'Konfirmasi';
        this.message = detail.message || '';
        this.confirmLabel = detail.confirmLabel || 'Hapus';
        this.danger = detail.danger !== false;
        this.open = true;
    },
}));

Alpine.start();

function formatRupiah(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(value) || 0));
}

function initCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        if (canvas.dataset.initialized) return;

        let config;
        try {
            config = JSON.parse(canvas.dataset.chart);
        } catch (error) {
            console.error('Invalid chart data', error);
            return;
        }

        const isMoney = config.money !== false;
        const options = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: config.legend === true,
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, font: { size: 11 } },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const value = context.parsed.y ?? context.parsed;
                            return ` ${context.label ?? context.dataset.label}: ${isMoney ? formatRupiah(value) : value}`;
                        },
                    },
                },
            },
        };

        if (config.type === 'bar') {
            options.scales = {
                x: { grid: { display: false }, ticks: { font: { size: 11 }, color: 'oklch(0.5 0.03 250)' } },
                y: {
                    beginAtZero: true,
                    grid: { color: 'oklch(0.92 0.01 250)', drawBorder: false },
                    ticks: {
                        font: { size: 11 },
                        color: 'oklch(0.5 0.03 250)',
                        callback: (value) => isMoney ? `${Math.round(value / 1000000)}jt` : value,
                    },
                },
            };
        }

        new Chart(canvas, {
            type: config.type,
            data: { labels: config.labels, datasets: config.datasets },
            options,
        });

        canvas.dataset.initialized = 'true';
    });
}

function boot() {
    createIcons({ icons });
    initCharts();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('alpine:initialized', () => {
    createIcons({ icons });
    initCharts();
});
