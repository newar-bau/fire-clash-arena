        </main>
        <footer class="flex-shrink-0 bg-gray-800 bottom-nav"><nav class="flex justify-around items-center p-3">
            <a href="index.php" class="flex flex-col items-center text-gray-400 hover:text-orange-500 w-1/5"><i class="fa-solid fa-house fa-lg"></i><span class="text-xs mt-1">Home</span></a>
            <a href="my_tournaments.php" class="flex flex-col items-center text-gray-400 hover:text-orange-500 w-1/5"><i class="fa-solid fa-trophy fa-lg"></i><span class="text-xs mt-1">Matches</span></a>
            <!-- FIX: Changed icon to fa-users for better compatibility -->
            <a href="pvp.php" class="flex flex-col items-center text-gray-400 hover:text-orange-500 w-1/5"><i class="fa-solid fa-users fa-lg"></i><span class="text-xs mt-1">PvP</span></a>
            <a href="wallet.php" class="flex flex-col items-center text-gray-400 hover:text-orange-500 w-1/5"><i class="fa-solid fa-wallet fa-lg"></i><span class="text-xs mt-1">Wallet</span></a>
            <a href="profile.php" class="flex flex-col items-center text-gray-400 hover:text-orange-500 w-1/5"><i class="fa-solid fa-user fa-lg"></i><span class="text-xs mt-1">Profile</span></a>
        </nav></footer>
    </div>
    <script>document.addEventListener('contextmenu', event => event.preventDefault());
        document.addEventListener('keydown', function (event) {
            if (event.ctrlKey === true && (event.key === '+' || event.key === '-')) {
                event.preventDefault();
            }
        });
        window.addEventListener('wheel', function(event) {
            if (event.ctrlKey === true) {
                event.preventDefault();
            }
        }, { passive: false });</script></body></html><?php ob_end_flush(); ?>