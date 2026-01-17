            </div>
        </div>
    </div>

    <script>
    function toggleSidebar() {
        document.querySelector('.admin-wrapper').classList.toggle('sidebar-collapsed');
    }

    function closeAlert() {
        document.getElementById('alertBox').style.display = 'none';
    }

    // Alert otomatik kapanma
    setTimeout(() => {
        const alert = document.getElementById('alertBox');
        if (alert) {
            alert.style.display = 'none';
        }
    }, 5000);
    </script>
</body>
</html>
