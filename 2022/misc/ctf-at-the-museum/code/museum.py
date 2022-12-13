#! /usr/bin/env python3
# By JuJu.

import base64
import io
from time import sleep

from PIL import Image

from selenium import webdriver
from selenium.webdriver.common.by import By

URL = "http://34.140.103.157:1235/"
IMAGE_REF = "image-reference.bmp"
IMG_PREFIX = "data:image/bpm;base64,"

driver = webdriver.Chrome()


def find_diff_pixel(ref, img):
    for x in range(ref.width):
        for y in range(ref.height):
            if (ref.getpixel((x, y)) != img.getpixel((x, y))):
                r, g, b = img.getpixel((x, y))

                return f"{x},{y},{r},{g},{b}"
    return "NotFound"


def diff_count(ref, img):
    d = 0
    for x in range(ref.width):
        for y in range(ref.height):
            if (ref.getpixel((x, y)) != img.getpixel((x, y))):
                d += 1
    return d


class Museum():

    def __init__(self):
        self.img_ref = Image.open(IMAGE_REF)
        self.room = 0

    def convert_to_images(self, b64_images):
        pil_images = []
        for i in range(len(b64_images)):
            img_src = b64_images[i].get_attribute('src')
            img_src = img_src[len(IMG_PREFIX):]
            img_buf = base64.b64decode(img_src)
            img_io = io.BytesIO(img_buf)
            img = Image.open(img_io)
            pil_images.append(img)

        return pil_images

    def find_image_with_one_diff(self, images, rotation=None, flip=False):
        next_password = None
        for i in range(3):
            img = images[i]
            if flip:
                img = img.transpose(Image.Transpose.FLIP_LEFT_RIGHT)
            if rotation is not None:
                img = img.transpose(rotation)
            if self.img_ref.width != img.width:
                continue
            diff_px = diff_count(self.img_ref, img)
            if diff_px == 1:
                diff_data = find_diff_pixel(self.img_ref, img)
                print(diff_data)
                next_password = base64.b64encode(diff_data.encode())
                break
        return next_password

    def get_next_room_passwd(self, images):
        assert len(images) >= 3
        print("")
        next_password = None
        rot = [None,
               Image.Transpose.ROTATE_90,
               Image.Transpose.ROTATE_180,
               Image.Transpose.ROTATE_270,
               Image.Transpose.FLIP_LEFT_RIGHT,
               Image.Transpose.FLIP_TOP_BOTTOM]

        for r in rot:
            next_password = self.find_image_with_one_diff(images, r)
            if next_password is not None:
                break

        if next_password is None:
            print("Password not found... Retrying with one more flip")
            for r in rot:
                next_password = self.find_image_with_one_diff(
                    images, r, flip=True)
                if next_password is not None:
                    break

        if next_password is None:
            print("Password not found...")

        return next_password

    def go_to_next_room(self):
        print("---")
        driver.get(URL)  # Navigate to url
        # print(driver.page_source)

        b64_imgs = driver.find_elements(By.TAG_NAME, 'img')
        imgs = self.convert_to_images(b64_imgs[0:3])
        next_password = self.get_next_room_passwd(imgs)
        print(next_password)

        key = driver.find_element(By.ID, 'key')
        key.clear()
        key.send_keys(next_password.decode())

        submit = driver.find_element(By.ID, 'submit')
        print(key)
        submit.click()

        self.room += 1


def main():
    print("Museum:")

    m = Museum()

    while m.room < 50:
        print("ROOM:", m.room)
        m.go_to_next_room()
        sleep(0.5)


if __name__ == '__main__':
    main()
