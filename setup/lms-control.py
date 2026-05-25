import RPi.GPIO as GPIO
import time
import os

# BUTTON PINS
BTN_POWER = 17
BTN_START = 27
BTN_STOP = 22
BTN_UPDATE = 23
BTN_RESET = 24

# LED PINS
LED_SERVER = 5
LED_HOTSPOT = 6

GPIO.setmode(GPIO.BCM)

buttons = [BTN_POWER, BTN_START, BTN_STOP, BTN_UPDATE, BTN_RESET]
leds = [LED_SERVER, LED_HOTSPOT]

for b in buttons:
    GPIO.setup(b, GPIO.IN, pull_up_down=GPIO.PUD_DOWN)

for l in leds:
    GPIO.setup(l, GPIO.OUT)
    GPIO.output(l, GPIO.LOW)

def start_server():
    os.system("systemctl start nginx")
    os.system("systemctl start php*-fpm")
    os.system("systemctl start mariadb")
    GPIO.output(LED_SERVER, GPIO.HIGH)

def stop_server():
    os.system("systemctl stop nginx")
    os.system("systemctl stop php*-fpm")
    os.system("systemctl stop mariadb")
    GPIO.output(LED_SERVER, GPIO.LOW)

def hotspot_on():
    os.system("nmcli con up Hotspot")
    GPIO.output(LED_HOTSPOT, GPIO.HIGH)

def hotspot_off():
    os.system("nmcli con down Hotspot")
    GPIO.output(LED_HOTSPOT, GPIO.LOW)

def update_system():
    GPIO.output(LED_SERVER, GPIO.LOW)
    os.system("nmcli con up 'Your Internet WiFi Name'")
    os.system("cd /var/www/bridgebox && git pull")
    os.system("nmcli con down 'Your Internet WiFi Name'")

def factory_reset():
    os.system("rm -rf /var/www/bridgebox/*")
    os.system("reboot")

print("LMS Hardware Control Ready")

while True:
    if GPIO.input(BTN_POWER):
        hotspot_on()
        start_server()
        time.sleep(1)

    if GPIO.input(BTN_START):
        start_server()
        time.sleep(1)

    if GPIO.input(BTN_STOP):
        stop_server()
        hotspot_off()
        time.sleep(1)

    if GPIO.input(BTN_UPDATE):
        update_system()
        time.sleep(1)

    if GPIO.input(BTN_RESET):
        factory_reset()

    time.sleep(0.1)
