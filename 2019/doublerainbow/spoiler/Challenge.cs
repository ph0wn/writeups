using System;
using System.Device.Gpio;
using System.Diagnostics;
using System.Threading;
using Iot.Device.CharacterLcd;

namespace DoubleRainbow
{

    public class Challenge
    {

        private const int nbButtons_ = 5;

        private static readonly int[] buttonPins_;
        private readonly int[] code1_;
        private readonly object codeLock_;
        private static readonly string[] colors_;
        private static readonly int[] ledPins_;
        private static readonly int[] pin2index_;

        private int[] code_;
        private int codeIndex_;
        private System.Device.Gpio.GpioController controller_;
        private Iot.Device.CharacterLcd.Lcd1602 lcd_;
        private string[] level1Flag;
        private string[] level2Flag;

        public Challenge()
        {
            code_ = new int[5];
            codeIndex_ = 0;
            codeLock_ = new System.Object();
            code1_ = new int[] { 0, 1, 0, 4, 3 };
            new string[2][0] = "ph0wn{our-hearts";
            new string[2][1] = "-in-vain}";
            level1Flag = new string[2];
            new string[2][0] = "ph0wn{fell-under";
            new string[2][1] = "-your-spell}";
            level2Flag = new string[2];
            controller_ = new System.Device.Gpio.GpioController();
            lcd_ = new Iot.Device.CharacterLcd.Lcd1602(19, 26, new int[] { 25, 24, 23, 18 }, -1, 1.0F, -1, null);
            OpenLEDs();
        }

        static Challenge()
        {
            new string[5][0] = "orange";
            new string[5][1] = "red";
            new string[5][2] = "white";
            new string[5][3] = "green";
            new string[5][4] = "blue";
            DoubleRainbow.Challenge.colors_ = new string[5];
            DoubleRainbow.Challenge.ledPins_ = new int[] { 13, 5, 9, 22, 17 };
            DoubleRainbow.Challenge.buttonPins_ = new int[] { 6, 11, 10, 27, 4 };
            DoubleRainbow.Challenge.pin2index_ = new int[] { -1, -1, -1, -1, 4, -1, 0, -1, -1, -1, 2, 1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 3 };
        }

        private void AnimateLEDs()
        {
            // trial
        }

        private void ButtonCallback(object sender, System.Device.Gpio.PinValueChangedEventArgs pinValueChangedEventArgs)
        {
            int i1 = DoubleRainbow.Challenge.pin2index_[pinValueChangedEventArgs.PinNumber];
            System.Diagnostics.Debug.Assert(i1 >= 0);
            object obj = codeLock_;
            bool flag1 = false;
            try
            {
                System.Threading.Monitor.Enter(obj, ref flag1);
                bool flag2 = codeIndex_ > 0;
                if (flag2)
                    LightLED(code_[codeIndex_ - 1], false);
                int i2 = codeIndex_;
                codeIndex_ = i2 + 1;
                code_[i2] = i1;
                LightLED(i1, true);
                WriteLines(System.String.Format("Color #{0} pressed", codeIndex_), DoubleRainbow.Challenge.colors_[i1], 0);
                bool flag3 = codeIndex_ >= code_.Length;
                if (flag3)
                    VerifyCode();
            }
            finally
            {
                if (flag1)
                    System.Threading.Monitor.Exit(obj);
            }
        }

        private void LightLED(int index, bool on)
        {
            controller_.Write(DoubleRainbow.Challenge.ledPins_[index], on ? System.Device.Gpio.PinValue.High : System.Device.Gpio.PinValue.Low);
        }

        private void OpenLEDs()
        {
            bool flag;

            int i1 = 0;
            while (flag)
            {
                int i2 = DoubleRainbow.Challenge.ledPins_[i1];
                controller_.OpenPin(i2, (System.Device.Gpio.PinMode)1);
                controller_.Write(i2, System.Device.Gpio.PinValue.Low);
                i1++;
                flag = i1 < 5;
            }
        }

        private void RegisterButtonsCallbacks()
        {
            // trial
        }

        public void Start()
        {
            // trial
        }

        private void VerifyCode()
        {
            // trial
        }

        private void Wait(double seconds)
        {
            // trial
        }

        private void Welcome()
        {
            WriteLines("ph0wn 2019 CTF", "Double Rainbow", 0);
            AnimateLEDs();
            WriteLines("Double Rainbow", "Ready", 0);
        }

        private void WriteFlag(string part1, string part2)
        {
            WriteLines(level1Flag[0].Substring(0, 6) + part1, part2 + "}", 10);
        }

        private void WriteLines(string line1, string line2, int sleep)
        {
            // trial
        }

    } // class Challenge

}

