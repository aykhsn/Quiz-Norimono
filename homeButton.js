class HomeButton {
    constructor(scene) {
        this.scene = scene;
        this.createHomeButton();
        this.createModal();
    }

    createHomeButton() {
        this.homeButton = this.scene.add.image(5, 5, 'home-button') // Use the image as the button
            .setOrigin(0, 0) // Align the image to the top-left corner
            .setScale(0.24) // Scale the image as needed
            .setInteractive() // Make the image interactive
            .on('pointerdown', () => {
                this.showModal();
            });
    }

    createModal() {
        // Create a semi-transparent black background that covers the entire screen
        this.modalBackground = this.scene.add.graphics()
            .fillStyle(0x000000, 0.5) // Black with 50% opacity
            .fillRect(0, 0, this.scene.scale.width, this.scene.scale.height);
        this.modalBackground.setInteractive(); // Make the background interactive
        this.modalBackground.setVisible(false); // Initially hidden
        this.modalBackground.setDepth(0); // Set background depth lower than modal

        // Create a container for the modal content
        this.modal = this.scene.add.container(this.scene.scale.width / 2, this.scene.scale.height / 2);

        // Adjust the modal size to fit the screen with some padding
        const modalWidth = Math.min(this.scene.scale.width * 0.8, 400);
        const modalHeight = Math.min(this.scene.scale.height * 0.6, 300);
        const borderRadius = 16; // 1rem in pixels

        this.modalBackgroundRect = this.scene.add.graphics();
        this.modalBackgroundRect.fillStyle(0xffffff, 1);
        this.modalBackgroundRect.fillRoundedRect(-modalWidth / 2, -modalHeight / 2, modalWidth, modalHeight, borderRadius);
        this.modal.add(this.modalBackgroundRect);

        // Create the modal content container
        this.modalContent = this.scene.add.container();
        this.modalContent.setPosition(0, 0); // Center modalContent
        this.modal.add(this.modalContent);

        // Add the "ホームに戻りますか？" text as an image
        this.modalText = this.scene.add.image(0, -modalHeight * 0.2, 'modal-text') // 'modal-text' is the key for your text image
            .setOrigin(0.5, 0.5)
            .setScale(modalWidth / this.scene.textures.get('modal-text').getSourceImage().width * 0.7); // Scale image to fit

        this.modalContent.add(this.modalText);

        // Calculate button size as a percentage of modal width
        const buttonSize = modalWidth * 0.35;

        // Load and add "はい" button
        this.yesButton = this.scene.add.image(-modalWidth * 0.2, modalHeight * 0.1, 'home-yes')
            .setInteractive()
            .setScale(buttonSize / this.scene.textures.get('home-yes').getSourceImage().width) // Adjust size based on image width
            .on('pointerdown', () => {
                window.location.href = 'https://pikopo.com/';
            });
        this.modalContent.add(this.yesButton);

        // Load and add "いいえ" button
        this.noButton = this.scene.add.image(modalWidth * 0.2, modalHeight * 0.1, 'home-no')
            .setInteractive()
            .setScale(buttonSize / this.scene.textures.get('home-no').getSourceImage().width) // Adjust size based on image width
            .on('pointerdown', () => {
                this.hideModal();
            });
        this.modalContent.add(this.noButton);

        // Add close button
        this.closeButton = this.scene.add.image(modalWidth / 2 - 10, -modalHeight / 2 + 10, 'modal-close') // 'modal-close' is the key for your close button image
            .setInteractive()
            .setScale(0.3) // Adjust size as needed
            .on('pointerdown', () => {
                this.hideModal();
            });
        this.modal.add(this.closeButton);

        this.modal.setVisible(false);
        this.modal.setDepth(1); // Set modal depth higher than background

        // Ensure the modal and background are interactive
        this.modal.setInteractive();
        this.modalBackground.setInteractive();

        // Add event listener for clicks on the modal background
        this.modalBackground.on('pointerdown', () => {
            this.hideModal();
        });

        // Prevent click events from passing through the modal
        this.modal.on('pointerdown', (pointer) => {
            pointer.stopPropagation();
        });
    }

    showModal() {
        this.modal.setVisible(true);
        this.modalBackground.setVisible(true); // Show the black background
    }

    hideModal() {
        this.modal.setVisible(false);
        this.modalBackground.setVisible(false); // Hide the black background
    }
}
