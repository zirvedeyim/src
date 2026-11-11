#import <Foundation/Foundation.h>
#import <UIKit/UIKit.h>
#import "FloatView.h"
#import "OverlayView.h"
#include "dolphins.h"
#import <mach-o/dyld.h>
#include <stdio.h>
#include <vector>
#include <iostream>
#include "../utils/module_tools.h"
#include "../dobby/dobby.h"
#include "../utils/log.h"

// Key Sistemi ve Menu siniflarini dahil et
#import "../KeySystem.mm"
#import "Menu.mm" 

#define kWidth  [UIScreen mainScreen].bounds.size.width
#define kHeight [UIScreen mainScreen].bounds.size.height
#define __fastcall
using namespace std;
typedef long kaddr;

// Modul Kontrolcusu
ModuleControl moduleControl;

// UI Baslatma Fonksiyonu
static void didFinishLaunching(CFNotificationCenterRef center, void *observer, CFStringRef name, const void *object, CFDictionaryRef info) {
    
    // 1. Oyun acildiktan 1 saniye sonra KEY EKRANINI ac
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(1.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        NSLog(@"[M7MD] Key ekrani aciliyor...");
        showKeyMenu(); 
    });
    
    // 2. Key dogrulandiginda (StartHack sinyali geldiginde) menuyu ve butonu ekle
    [[NSNotificationCenter defaultCenter] addObserverForName:@"StartHack" object:nil queue:[NSOperationQueue mainQueue] usingBlock:^(NSNotification * _Nonnull note) {
        
        NSLog(@"[M7MD] Key dogrulandi, Menu ve Buton yukleniyor...");
        
        // Menu'yu baslat
        mi* menuWindow = [[mi alloc] initWithFrame:&moduleControl];
        
        // Overlay (Arka plan cizim) katmanini baslat
        OverlayView* overlayView = [[OverlayView alloc] initWithFrame:[UIScreen mainScreen].bounds:&moduleControl:menuWindow];
        [[UIApplication sharedApplication].keyWindow addSubview:overlayView];
        
        // Kucuk butonu (FloatView) ekle
        FloatView* floatView = [[FloatView alloc] initWithFrame:CGRectMake(489, 58, 45, 45):&moduleControl];
        [[UIApplication sharedApplication].keyWindow addSubview:floatView];
        
        NSLog(@"[M7MD] Menu ve Buton basariyla yuklendi!");
    }];
}

// Kutuphane Giris Fonksiyonu (Constructor)
__attribute__((constructor)) static void initialize() {
    // Uygulama basladiginda didFinishLaunching fonksiyonunu dinle
    CFNotificationCenterAddObserver(CFNotificationCenterGetLocalCenter(), NULL, &didFinishLaunching, (CFStringRef)UIApplicationDidFinishLaunchingNotification, NULL, CFNotificationSuspensionBehaviorDrop);
}
