<?php
/*
Template Name: のりものどれかな？
*/
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> <?php Arkhe::root_attrs(); ?>>
<head>
<meta charset="utf-8">
<meta name="format-detection" content="telephone=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, viewport-fit=cover">
<?php
	wp_head();
	$setting = Arkhe::get_setting(); // SETTING取得
?>
<style>
    body {
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #A1CBC4;
        overflow: hidden;
    }
</style>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/phaser@3/dist/phaser.min.js"></script>
    <script>
    const assetsUrl = "<?php echo get_template_directory_uri(); ?>/assets/games";

    const itemTypes = [
        'ambulance', 'patrol_car', 'shovel_car', 'taxi', 'bus', 'truck', 'dump_truck', 
        'fire_truck-ladder', 'fire_truck-pump', 'crane_truck', 'forklift_truck', 'mixer_truck', 
        'cleaning_truck', 'car_carrier', 'mail_truck', 'bulldozer'
    ];

    class StartScene extends Phaser.Scene {
        constructor() {
            super({ key: 'StartScene' });
        }

        preload() {
            this.load.image('button-start',  `${assetsUrl}/button-start.png`);
            preloadAssets(this);
        }

        create() {
            const buttonWidth = Math.min(this.scale.width - 80, 400);
            const startButton = this.add.image(this.scale.width / 2, this.scale.height / 2, 'button-start')
                .setDisplaySize(buttonWidth, buttonWidth / 2)
                .setInteractive();
            startButton.on('pointerdown', () => {
                if (this.sound.context.state === 'suspended') {
                    this.sound.context.resume();
                }
                this.sound.play('click');
                this.time.delayedCall(500, () => {
                    this.sound.play('start');
                    this.scene.start('QuizScene');
                });
            });
        }
    }

    class QuizScene extends Phaser.Scene {
        constructor() {
            super({ key: 'QuizScene' });
            this.currentQuestion = 0;
            this.correctAnswers = 0;
            this.usedItems = [];
            this.wrongAttempts = 0;
            this.timers = [];
            this.sounds = {};
            this.correctItems = [];
            this.clickCooldown = false;
        }

        init() {
            this.currentQuestion = 0;
            this.correctAnswers = 0;
            this.usedItems = [];
            this.wrongAttempts = 0;
            this.correctItems = [];
            this.clearTimers();
            this.stopAllSounds();
        }

        create() {
            this.questions = this.createQuestions();
            this.showQuestion();
        }

        createQuestions() {
            const questions = [];
            let availableItems = itemTypes.slice();
            for (let i = 0; i < 6; i++) {
                const correctItem = Phaser.Utils.Array.RemoveRandomElement(availableItems);
                const options = Phaser.Utils.Array.Shuffle([correctItem, ...Phaser.Utils.Array.Shuffle(availableItems).slice(0, 2)]);
                questions.push({ correctItem, options });
                this.usedItems.push(correctItem);
                availableItems = availableItems.filter(item => !this.usedItems.includes(item));
            }
            return questions;
        }

        showQuestion() {
            if (this.currentQuestion >= this.questions.length) {
                this.scene.start('ResultScene', { correctAnswers: this.correctAnswers, correctItems: this.correctItems });
                return;
            }
            const { correctItem, options } = this.questions[this.currentQuestion];
            this.wrongAttempts = 0;
            this.clearTimers();
            this.stopAllSounds();

            this.input.enabled = false;

            let delayTime = 0;
            if (this.currentQuestion === 0) {
                delayTime = 2200;
            }

            this.timers.push(this.time.delayedCall(delayTime, () => {
                this.sounds.quiz = this.sound.add('quiz');
                if (this.sounds.quiz) {
                    this.sounds.quiz.play();
                }
            }));
            this.timers.push(this.time.delayedCall(delayTime + 800, () => {
                this.sounds.correctItem = this.sound.add(correctItem);
                if (this.sounds.correctItem) {
                    this.sounds.correctItem.play();
                    this.input.enabled = true;
                }
            }));

            this.sounds.bgmSound = this.sound.add('bgm', { loop: true });
            this.timers.push(this.time.delayedCall(delayTime + 2500, () => {
                if (this.sounds.bgmSound) {
                    this.sounds.bgmSound.play();
                }
            }));

            const positions = this.getUniquePositions(3, this.scale.width, this.scale.height);

            this.currentImages = [];
            options.forEach((item, index) => {
                const itemImage = this.add.image(positions[index].x, positions[index].y, item)
                    .setInteractive();

                if (this.scale.width > this.scale.height) {
                    itemImage.setScale(Math.min(1, (this.scale.width / 3 - 40) / itemImage.width));
                } else if (this.scale.width * 5 / 8 > this.scale.height / 3) {
                    itemImage.setScale(Math.min(1, ((this.scale.height / 3) - 40) / itemImage.height));
                } else {
                    itemImage.setScale(Math.min(1, ((this.scale.width) - 80) / itemImage.width));
                }

                itemImage.on('pointerdown', () => {
                    if (this.clickCooldown) return;
                    this.clickCooldown = true;
                    this.time.delayedCall(500, () => { this.clickCooldown = false; });

                    this.input.enabled = false;

                    this.clearTimers();
                    this.stopAllSounds();

                    if (item === correctItem) {
                        this.sounds.correct = this.sound.add('correct');
                        if (this.sounds.correct) {
                            this.sounds.correct.play();
                        }
                        this.correctAnswers++;
                        this.currentQuestion++;
                        this.correctItems.push(correctItem); // Store the correct item

                        this.time.delayedCall(1000, () => {
                            this.sounds.seikai = this.sound.add('seikai');
                            if (this.sounds.seikai) {
                                const praiseSounds = ['voice-sugoi', 'voice-yattane', 'voice-omigoto'];
                                const randomPraise = Phaser.Utils.Array.GetRandom(praiseSounds);
                                this.sound.play(randomPraise);
                                
                                this.time.delayedCall(1000, () => {
                                    this.sounds.seikai.play();
                                });
                            }
                        });

                        this.tweens.add({
                            targets: itemImage,
                            x: this.scale.width / 2,
                            y: this.scale.height / 2,
                            duration: 1000,
                            ease: 'Power2'
                        });

                        this.currentImages.forEach(image => {
                            if (image !== itemImage) {
                                this.tweens.add({
                                    targets: image,
                                    alpha: 0,
                                    duration: 1000,
                                    ease: 'Power2',
                                    onComplete: () => {
                                        image.destroy();
                                    }
                                });
                            }
                        });

                        const circle = this.add.circle(this.scale.width / 2, this.scale.height / 2, 0, 0xE7CAD6)
                            .setDepth(-1);
                        this.tweens.add({
                            targets: circle,
                            radius: Math.min(this.scale.width, this.scale.height) / 2.2,
                            duration: 1000,
                            ease: 'Power2'
                        });

                        if (this.currentQuestion < this.questions.length) {
                            this.time.delayedCall(4000, () => {
                                this.sounds.next = this.sound.add('next');
                                if (this.sounds.next) {
                                    this.sounds.next.play();
                                }
                            });
                            this.time.delayedCall(5400, () => {
                                this.clearOptions();
                                circle.destroy();
                                this.showQuestion();
                                this.input.enabled = true;
                            });
                        } else {
                            this.time.delayedCall(4000, () => {
                                this.clearOptions();
                                circle.destroy();
                                this.showQuestion();
                                this.input.enabled = true;
                            });
                        }
                    } else {
                        this.wrongAttempts++;

                        this.sounds.tryAgain = this.sound.add('try_again');
                        if (this.sounds.tryAgain) {
                            this.sounds.tryAgain.play();
                        }

                        this.sounds.chigauyo = this.sound.add('chigauyo');
                        this.time.delayedCall(1000, () => {
                            if (this.sounds.chigauyo) {
                                this.sounds.chigauyo.play();
                            }
                        });

                        if (this.wrongAttempts >= 3) {
                            this.currentQuestion++;

                            if (this.currentQuestion < this.questions.length) {
                                this.time.delayedCall(3000, () => {
                                    this.sounds.next = this.sound.add('next');
                                    if (this.sounds.next) {
                                        this.sounds.next.play();
                                    }
                                });
                                this.time.delayedCall(4400, () => {
                                    this.clearOptions();
                                    this.showQuestion();
                                    this.input.enabled = true;
                                });
                            } else {
                                this.time.delayedCall(3000, () => {
                                    this.clearOptions();
                                    this.showQuestion();
                                    this.input.enabled = true;
                                });
                            }                                
                        } else {
                            this.time.delayedCall(2700, () => {
                                this.sounds.correctItem = this.sound.add(correctItem);
                                if (this.sounds.correctItem) {
                                    this.sounds.correctItem.play();
                                }
                            });
                            this.time.delayedCall(4200, () => {
                                if (this.sounds.bgmSound) {
                                    this.sounds.bgmSound.play();
                                }
                                this.input.enabled = true;
                            });
                        }
                    }
                });
                this.currentImages.push(itemImage);
            });
        }

        clearOptions() {
            this.currentImages.forEach(image => image.destroy());
            this.currentImages = [];
        }

        clearTimers() {
            this.timers.forEach(timer => timer.remove(false));
            this.timers = [];
        }

        stopAllSounds() {
            for (let key in this.sounds) {
                if (this.sounds[key] && this.sounds[key].isPlaying) {
                    this.sounds[key].stop();
                }
            }
            if (this.sounds.bgmSound && this.sounds.bgmSound.isPlaying) {
                this.sounds.bgmSound.stop();
            }
        }

        getUniquePositions(count, width, height) {
            const positions = [];
            const padding = 40;
            if (width > height) {
                const stepX = (width - 2 * padding) / 3;
                const y = height / 2;
                for (let i = 0; i < count; i++) {
                    positions.push({ x: padding + stepX / 2 + i * stepX, y: y });
                }
            } else {
                const stepY = (height - 2 * padding) / 3;
                const x = width / 2;
                for (let i = 0; i < count; i++) {
                    positions.push({ x: x, y: padding + stepY / 2 + i * stepY });
                }
            }
            return positions;
        }
    }

    class ResultScene extends Phaser.Scene {
        constructor() {
            super({ key: 'ResultScene' });
        }

        init(data) {
            this.correctAnswers = data.correctAnswers;
            this.correctItems = data.correctItems;
        }

        preload() {
            this.load.image('button-retry',  `${assetsUrl}/button-retry.png`);
            this.load.audio('tettere',  `${assetsUrl}/tettere.mp3`);
            this.load.audio('voice-mataasobinikitene',  `${assetsUrl}/voice-mataasobinikitene.mp3`);
            this.load.audio('voice-seikaishitane-0',  `${assetsUrl}/voice-seikaishitane-0.mp3`);
            this.load.audio('voice-seikaishitane-1',  `${assetsUrl}/voice-seikaishitane-1.mp3`);
            this.load.audio('voice-seikaishitane-2',  `${assetsUrl}/voice-seikaishitane-2.mp3`);
            this.load.audio('voice-seikaishitane-3',  `${assetsUrl}/voice-seikaishitane-3.mp3`);
            this.load.audio('voice-seikaishitane-4',  `${assetsUrl}/voice-seikaishitane-4.mp3`);
            this.load.audio('voice-seikaishitane-5',  `${assetsUrl}/voice-seikaishitane-5.mp3`);
            this.load.audio('voice-seikaishitane-all',  `${assetsUrl}/voice-seikaishitane-all.mp3`);
        }

        create() {
            const buttonWidth = Math.min(this.scale.width - 80, 400);

            const voiceFiles = [
                'voice-seikaishitane-0',
                'voice-seikaishitane-1',
                'voice-seikaishitane-2',
                'voice-seikaishitane-3',
                'voice-seikaishitane-4',
                'voice-seikaishitane-5',
                'voice-seikaishitane-all'
            ];

            this.sound.play('tettere');

            const voiceFile = this.correctAnswers >= 6 ? voiceFiles[6] : voiceFiles[this.correctAnswers];
            this.time.delayedCall(2000, () => {
                this.sound.play(voiceFile);
            });
            if(this.correctAnswers > 0) {
                this.time.delayedCall(4800, () => {
                    this.sound.play('voice-mataasobinikitene');
                });
            }

            const retryButton = this.add.image(this.scale.width / 2, this.scale.height / 2, 'button-retry')
                .setDisplaySize(buttonWidth, buttonWidth / 2)
                .setInteractive();
            retryButton.on('pointerdown', () => {
                this.sound.play('retry');
                if (this.sound.context.state === 'suspended') {
                    this.sound.context.resume();
                }
                this.sound.play('click');
                this.scene.start('QuizScene');
            });

            // Display correct items above and below the retry button
            this.displayCorrectItems(buttonWidth);
        }

        displayCorrectItems(buttonWidth) {
            const itemSize = buttonWidth / 3; // クイズの画像の3分の1のサイズ
            const startX = this.scale.width / 2 - itemSize;
            const itemYAbove = this.scale.height / 4;
            const itemYBelow = this.scale.height * 3 / 4;

            for (let i = 0; i < this.correctItems.length; i++) {
                const itemImage = this.add.image(startX + (i % 3) * itemSize, i < 3 ? itemYAbove : itemYBelow, this.correctItems[i])
                    .setDisplaySize(itemSize, itemSize * 5 / 8)
                    .setAlpha(0);

                this.tweens.add({
                    targets: itemImage,
                    alpha: 1,
                    duration: 1000,
                    delay: 100 * i
                });
            }
        }
    }

    function preloadAssets(scene) {
        itemTypes.forEach(item => {
            scene.load.image(item, `${assetsUrl}/${item}-5_8.png`);
        });
        scene.load.audio('start',  `${assetsUrl}/voice-quiz-norimono.mp3`);
        scene.load.audio('next',  `${assetsUrl}/voice-tsuginomondai.mp3`);
        scene.load.audio('click',  `${assetsUrl}/chiroriro.mp3`);
        scene.load.audio('quiz',  `${assetsUrl}/quiz-jajan.mp3`);
        scene.load.audio('bgm',  `${assetsUrl}/lets_cooking.mp3`);
        itemTypes.forEach(item => {
            scene.load.audio(item, `${assetsUrl}/voice-${item}.mp3`);
        });
        scene.load.audio('correct',  `${assetsUrl}/quiz-pinpon.mp3`);
        scene.load.audio('seikai',  `${assetsUrl}/voice-seikai.mp3`);
        scene.load.audio('voice-sugoi',  `${assetsUrl}/voice-sugoi.mp3`);
        scene.load.audio('voice-yattane',  `${assetsUrl}/voice-yattane.mp3`);
        scene.load.audio('voice-omigoto',  `${assetsUrl}/voice-omigoto.mp3`);
        scene.load.audio('try_again',  `${assetsUrl}/quiz-bu.mp3`);
        scene.load.audio('chigauyo',  `${assetsUrl}/voice-chigauyo.mp3`);
        scene.load.audio('result',  `${assetsUrl}/chiroriro.mp3`);
        scene.load.audio('retry',  `${assetsUrl}/voice-mouichido.mp3`);
    }

    const config = {
        type: Phaser.AUTO,
        width: window.innerWidth,
        height: window.innerHeight,
        backgroundColor: '#A1CBC4',
        scale: {
            mode: Phaser.Scale.FIT,
            autoCenter: Phaser.Scale.CENTER_BOTH
        },
        scene: [StartScene, QuizScene, ResultScene]
    };

    const game = new Phaser.Game(config);

    window.addEventListener('resize', () => {
        game.scale.resize(window.innerWidth, window.innerHeight);
    });

    </script>
</body>
</html>