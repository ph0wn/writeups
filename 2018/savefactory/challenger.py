#!/usr/bin/python

from opcua import ua
from tragen import *
from IPython import embed
import struct
import sys, os
import time
import random
import datetime


class Updater(Thread):

    def __init__(self, varz, regular_update=True, period=1.5, val_stddev=0.15, pd_stddev=0.6):
        super(Updater, self).__init__()
        self.nodes = varz
        self._updating = True
        self._period = period
        self._val_sd = val_stddev
        self._pd_sd = pd_stddev
        self._reg_ud = regular_update

    def stop(self):
        self._updating = False

    def drop_var(self, var):
        idx = self.nodes.index(var)
        self.nodes.pop(idx)

    def run(self):
        while self._updating:
            for node in self.nodes:
                new_val = random.normalvariate(self.node.get_value(), self._val_sd)
                self.node.set_value(new_val)
                zztime = self._period if self._reg_ud else random.normalvariate(self._period, self._pd_sd)
                sleep(zztime)


class Timer(Thread):

    def __init__(self, event, delay=30, interval=90):
        super(Timer, self).__init__()
        self.event = event
        self.delay = delay
        self.interval = interval
        self._running = True

    def stop(self):
        self._running = False

    def run(self):
        sleep(self.delay)
        t_init = time.time()
        half, quarter = False, False
        while self._running:
            if (time.time() - t_init) < self.interval-1:
                if ((self.interval - (time.time() - t_init)) < self.interval/4) and not quarter:
                    quarter = True
                    self.event.trigger(message="[/!\] Alert: ONLY %d seconds are left. Time is running out!"%(int(self.interval/4)))
                elif ((self.interval -(time.time() - t_init)) < self.interval/2) and not half:
                    half = True
                    self.event.trigger(message="[/!\] Alert: %d seconds left. Please hurry up!"%(int(self.interval/2)))
            else:
                self.event.trigger(message="[/!\] Warning: The factory is going to explode! Evacuate the facility IMMEDIATELY.")
                self.event.trigger(message="[/!\] Game Over")
                sleep(1)
                break



class Challenger(Thread):

    def __init__(self, server, event, varz, updater, logbox, timer_th, mini_chall, delay=30, interval=90):
        super(Challenger, self).__init__()
        self.server = server
        self.event = event
        self.vars = varz
        self.updater = updater
        self.logbox = logbox
        self.timer = timer_th
        self.mchall = mini_chall
        self.delay = delay
        self.interval = interval


    def temperature_mchall(self):
        while True: # Chosing a random temperature sensor
            idx = random.choice(range(len(self.vars)))
            var = self.vars[idx]
            if var.get_browse_name().Name != "temperature":
                continue
            else:
                break
        # Stop updating the sensor (simulating a dysfunctional sensor)
        self.updater.drop_var(var)
        self.vars.pop(idx)

        # Giving the player/team the right to operate the cooling system
        # (Also faking the aceess rights misconfiuration)
        cooling_system = var.get_parent().get_child("cooling_sys")
        cooling_system.set_writable()

        # Depending on which state is the cooling system at, we hint at the player/team to toggle it up
        state = cooling_system.get_value()
        if cooling_system.get_value() == "OFF":
            self.event.trigger(message="[/!\] Warning: Temperature is way above critical value!")
        else:
            self.event.trigger(message="[/!\] Warning: Temperature is way below critical value!")
        dept = var.get_parent().get_parent()
        unit = dept.get_parent()
        self.event.trigger(message="[/!\] Warning: The cooling system of %s at %s is NOT responding!"%(unit.get_browse_name().Name, dept.get_browse_name().Name))
        self.logbox.add_variable(2, "log24", "[%s] The cooling system of %s at %s is NOT responding!"%(datetime.datetime.now().strftime("%d-%m-%Y %H:%M"), unit.get_browse_name().Name, dept.get_browse_name().Name))
        self.event.trigger(message="Something is going to explode in less than %d seconds"%(self.interval))
        while (cooling_system.get_value() == state) and self.timer.isAlive():
            sleep(0.25)
        if cooling_system.get_value() != state:
            #self.timer.stop()
            self.event.trigger(message="[#] Notif: The cooler has been toggled up! Everything is back on track.")

    def pressure_mchall(self):
        while True: # Chosing a random pressure regulator
            idx = random.choice(range(len(self.vars)))
            var = self.vars[idx]
            if var.get_browse_name().Name != "pressure":
                continue
            else:
                break
        # Stop updating the sensor (simulating a dysfunctional sensor)
        self.updater.drop_var(var)
        self.vars.pop(idx)
        
        # Giving the player/team the right to operate the regulator
        # (Also faking the access rights misconfiuration)
        var.set_writable()

        dept = var.get_parent().get_parent()
        unit = dept.get_parent()
        state = var.get_parent().get_child("state").get_value()
        pressure_val = var.get_value()
        if state == "LOW":
            min_required = pressure_val*1.75
            self.event.trigger(message="[/!\] Warning: Pressure at %s is critically LOW!"%(unit.get_browse_name().Name))
            sleep(1)
            self.logbox.add_variable(2, "log25", "[%s] Pressure at %s is critically HIGH (Way below %s)! The regulator at %s is NOT responding!"%(datetime.datetime.now().strftime("%d-%m-%Y %H:%M"), unit.get_browse_name().Name, min_required, dept.get_browse_name().Name))
            self.event.trigger(message="[/!\] Warning: The pressure regulator of %s at %s is NOT responding!"%(dept.get_browse_name().Name, unit.get_browse_name().Name))
            self.event.trigger(message="[/!\] Warning: It should be set to at least %d in less than %d seconds or the unit will go down!!"%(min_required, self.interval))
            while (pressure_val < min_required) and self.timer.isAlive():
                pressure_val = var.get_value()
        else:
            min_required = pressure_val*0.35
            self.event.trigger(message="[/!\] Warning: Pressure at %s is critically HIGH!"%(unit.get_browse_name().Name))
            sleep(1)
            self.logbox.add_variable(2, "log25", "[%s] Pressure at %s is critically HIGH (Way above %s)! The regulator of %s is NOT responding!"%(datetime.datetime.now().strftime("%d-%m-%Y %H:%M"), unit.get_browse_name().Name, min_required, dept.get_browse_name().Name))
            self.event.trigger(message="[/!\] Warning: The pressure regulator of %s at %s is NOT responding!"%(dept.get_browse_name().Name, unit.get_browse_name().Name))
            self.event.trigger(message="[/!\] Warning: It should be set to %d at maximum in less than %d seconds or the unit will go down!!"%(min_required, self.interval))
            while (pressure_val > min_required) and self.timer.isAlive():
                pressure_val = var.get_value()
        if self.timer.isAlive():
            #self.timer.stop()
            self.event.trigger(message="[#] Notif: The regulator has finally replied back! Everything is back on track.")

    
    def run(self):
        self.timer.start()
        self.logbox.add_variable(2, "log23", "[%s] Reset failing nodes to acceptable value."%datetime.datetime.now().strftime("%d-%m-%Y %H:%M"))
        sleep(self.delay)
        if self.mchall == "temperature":
            self.temperature_mchall()
        else:
            self.pressure_mchall()



def main():

    # Defining OPC UA server's data graph
    #################################################################################################
    groupings = [ ['North', 'East', 'West', 'South'], \
                  ['Center', 'Bottom', 'Top', 'Stash'], \
                  ['Right_side', 'upper_left', 'bottom_left'], \
                  ['UATX_315', 'UATX_325', 'UAR_V32', 'UARX_V32'], \
                  ['AAM_992', 'UTX_3', 'SR_L3', 'XR_L3'],
            ]

    changing_vars= []

    # 1] Graph tree:     
    ua_graph = UaDataStruct()
    for i in range(1,6):
        vars()['unit_%s'%i] = ua_graph.add_folder("unit_%s"%i)
        random.seed(int((time.time()%1)*(10**(int((time.time()%1)*10)))))
        grouping = groupings.pop(random.randint(0,5-i))
        for group in grouping:
            tmp_fldr = ua_graph.add_folder(group, parent_name="unit_%s"%i)
            empty = True
            if random.uniform(0, 1) > 0.4:
                sens_name = "T@sensor_u%s_%s"%(i, group[:4])
                ua_graph.add_object(sens_name, folder_node=tmp_fldr, ua_variables=[("temperature", random.randint(-50, 100))], ua_properties=[("state", "NORMAL"), ("cooling_sys", "OFF" if random.uniform(0, 1) > 0.5 else "ON"), ("connected", "true")])
                changing_vars.append((["unit_%s"%i, group, "temperature"], sens_name))
                empty = False

            if (random.uniform(0, 1) > 0.4) or empty:
                reg_name = "P@regulator_u%s_%s"%(i, group[:4])
                ua_graph.add_object(reg_name, folder_node=tmp_fldr, ua_variables=[("pressure", random.randint(0, 20))], ua_properties=[("state", "LOW" if i<3 else "NORMAL"), ("connected", "TRUE")])
                changing_vars.append((["unit_%s"%i, group, "pressure"], reg_name))



    #################################################################################################
    # 2] Exfiltrated data:     
    fldr1 = ua_graph.add_folder("}HGva_J;uv99")
    fldr2 = ua_graph.add_folder("0J,BICyQu9\>@")
    fldr3 = ua_graph.add_folder("BYsqOwY-")
    fldr4 = ua_graph.add_folder("7PFG6qG+>qtlx/t")
    folders = [fldr1, fldr3, fldr3, fldr4]

    ## After creating folders.
    #f = open("veryRand0mFl1nam3.tgz", "rb").read()
    f = open("/challenger/veryRand0mFl1nam3.tgz", "rb").read()
    fragments = []
    for i in range(0, len(f), 4):
        fragments.append((i/4, struct.unpack(">L", bytes(f)[i:i+4])[0]))
    while fragments:
        idx = random.randint(0, len(fragments)-1)
        num, frg = fragments.pop(idx)
        fldr = random.choice(folders)
        if not isinstance(num, int): # But, why?
            num = num[0]
        #ua_graph.add_variable("frg_%d"%(num), var_value=frg, par_node=fldr)
        ua_graph.add_property("frg_%d"%(num), hex(frg)[2:], par_node=fldr)


    #################################################################################################
    # Server stuff.
    try:
        port = int(sys.argv[1])
    except:
        os._exit(1)
    if port<=1025 or port>9999:
        os._exit(1)
    srv_url="opc.tcp://0.0.0.0:%s/FearFactory/supervision_unit/"%(port)
    srv_name="Supervizor"    
    server = ServerTragen(srv_url, name=srv_name)     
    server.init(data_nodes=ua_graph)    
    root = server.get_root_node()
    objs = server.get_objects_node()

    # Main board.
    central_unit = root.add_object(2, "MainBoard")
    event_type = server.create_custom_event_type(2, "MainBoardNotif", ua.ObjectIds.BaseEventType)
    notif_generator = server.get_event_generator(event_type, central_unit)

    # Starting updaters.
    updated_vars = [root.get_child(["0:%s"%cv[0][0], "0:%s"%cv[0][1], "0:%s"%cv[1], "0:pressure" if cv[1][0]=='P' else "0:temperature"]) for cv in changing_vars]
    updater = Updater(updated_vars)
    updater.start()

    # Black flag!
    # TODO: figure out how to deny reading access
    now = datetime.datetime.now()
    bbox = root.add_object(2, "BlackBox")
    bbox.add_variable(2, "log21", "[%s] Suspicious read and write operations!"%datetime.datetime.now().strftime("%d-%m-%Y %H:%M"))

    # Challenge:
    mini_challenges = ["temperature", "pressure"]

    # 1st round
    #timer = Timer(notif_generator, delay=30, interval=240)
    timer = Timer(notif_generator, delay=30, interval=340)
    which_mchall = mini_challenges.pop(0 if random.uniform(0, 1) > 0.5 else 1)
    chall_thread = Challenger(server, notif_generator, updated_vars, updater, bbox, timer, which_mchall, delay=30, interval=340)
    chall_thread.start()
    chall_thread.join()
    if timer.isAlive():
        timer.stop()
        bbox.add_variable(2, "log25", "[%s] Apparently a client has been connected for quite a while now!"%datetime.datetime.now().strftime("%d-%m-%Y %H:%M"))
        sleep(10)
    else: #Failed :/
        updater.stop()
        updater.join()
        server.stop()
        os._exit(0)


    # 2nd round
    timer = Timer(notif_generator, delay=15, interval=150)
    chall_thread = Challenger(server, notif_generator, updated_vars, updater, bbox, timer, mini_challenges[0], delay=15, interval=150)
    chall_thread.start()
    chall_thread.join()
    if timer.isAlive():
        timer.stop()
    else: #Failed :/
        updater.stop()
        server.stop()
        os._exit(0)


    # Made it!
    bbox.add_variable(2, "log26", "[%s] Everything has been handled very well. We're back to normal. Congratz! Here's your flag: ph0wn{0pc-M4n_c0mIng_tO_The_R3SCue}"%datetime.datetime.now().strftime("%d-%m-%Y %H:%M"))


if __name__ == '__main__':
    main()




