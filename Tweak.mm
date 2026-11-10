#import <JRMemory/JRMemory.h>
#import <Foundation/Foundation.h>
#import <UIKit/UIKit.h>
#import <mach/mach.h>
#import <mach-o/dyld.h>
#import <mach/mach_traps.h>

// --- YENİ EKLENEN KEY VE BUTON SİSTEMİ İMPORTLARI ---
#import "KeySystem.mm"
#import "View/FloatView.h" 
// Eğer ModuleControl hatası alırsan şu satırı aç:
// #import "Menux/Menu.h" 

//**********************************************//
//**This Header File is used in combination****//
//**with a dynamic Library and must be rewritten**//
//**if you want to use it for another purpose****//
//**********************************************//

//******************************************//
//**Credits: Razzile & HackJack**//
//****************************************//

// Global variables to store the state of CheckBoxes
bool LockStatus = false;
bool SpeedPlayer = false;

bool hasASLR() {
    const struct mach_header *mach;
    mach = _dyld_get_image_header(0);

    if (mach->flags & MH_PIE) {
        return TRUE;
    } else {
        return FALSE;
    }
}

long long get_slide() {
    return _dyld_get_image_vmaddr_slide(0);
}

long long calculateAddress(long long offset) {
    if (hasASLR()) {
        long long slide = get_slide();
        return (slide + offset);
    } else {
        return offset;
    }
}

bool getType(unsigned int data) {
    int a = data & 0xffff8000;
    int b = a + 0x00008000;
    int c = b & 0xffff7fff;
    return c;
}

bool vm_writeData(long long offset, unsigned int data) {
    kern_return_t err;
    mach_port_t port = mach_task_self();
    long long address = calculateAddress(offset);

    err = vm_protect(port, (vm_address_t)address, sizeof(data), NO, VM_PROT_READ | VM_PROT_WRITE | VM_PROT_COPY);
    if (err != KERN_SUCCESS) {
        return FALSE;
    }

    if(getType(data)) {
        data = CFSwapInt32(data);
        err = vm_write(port, (vm_address_t)address, (vm_offset_t)&data, sizeof(data));
    } else {
        data = (unsigned short)data;
        data = CFSwapInt16(data);
        err = vm_write(port, (vm_address_t)address, (vm_offset_t)&data, sizeof(data));
    }
    
    if (err != KERN_SUCCESS) {
        return FALSE;
    }
    
    err = vm_protect(port, (vm_address_t)address, sizeof(data), NO, VM_PROT_READ | VM_PROT_EXECUTE);

    return TRUE;
}

void setLockStatus(bool state) {
    LockStatus = state;
    if (LockStatus) {
        bool success = vm_writeData(0x102CA170C, 0x1F2003D5);
        if (success) {
            NSLog(@"LockStatus enabled successfully");
        } else {
            NSLog(@"Failed to enable LockStatus");
        }
    } else {
        NSLog(@"LockStatus disabled");
    }
}

void setSpeedPlayer(bool state) {
    SpeedPlayer = state;
    if (SpeedPlayer) {
        bool success = vm_writeData(0x102C10D84, 0x0810281E);
        if (success) {
            NSLog(@"SpeedPlayer enabled successfully");
        } else {
            NSLog(@"Failed to enable SpeedPlayer");
        }
    } else {
        NSLog(@"SpeedPlayer disabled");
    }
}

// ==========================================
// YENİ EKLENEN KISIM: KEY VE BUTON BAŞLATICI
// ==========================================
%ctor {
    // 1. Oyun açıldıktan 5 saniye sonra Key Menüsünü aç
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        showKeyMenu(); 
    });
    
    // 2. Key doğrulandığında (StartHack sinyali geldiğinde) FloatView butonunu ekle
    [[NSNotificationCenter defaultCenter] addObserverForName:@"StartHack" object:nil queue:[NSOperationQueue mainQueue] usingBlock:^(NSNotification * _Nonnull note) {
        
        // Butonu oluştur (Eğer ModuleControl tanımlı değilse burada hata verebilir)
        ModuleControl *control = [[ModuleControl alloc] init]; 
        
        FloatView *floatBtn = [FloatView sharedInstance:control];
        
        // Butonun ekrandaki boyutu ve yeri (Sol üstte 60x60 boyutunda)
        floatBtn.frame = CGRectMake(10, 150, 60, 60); 
        
        // Oyunun ana penceresine butonu ekle
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            [window addSubview:floatBtn];
            NSLog(@"[M7MD] Key dogrulandi, buton basariyla eklendi!");
        } else {
            NSLog(@"[M7MD] Pencere bulunamadi, buton eklenemedi!");
        }
    }];
}

// ==========================================
// ORİJİNAL SINIFLAR (Aynen korundu)
// ==========================================

@interface TweakViewController : UIViewController
@property (nonatomic, strong) UISwitch *lockStatusSwitch;
@property (nonatomic, strong) UISwitch *speedPlayerSwitch;
@end

@implementation TweakViewController

- (void)viewDidLoad {
    [super viewDidLoad];
    
    self.lockStatusSwitch = [[UISwitch alloc] init];
    [self.lockStatusSwitch addTarget:self action:@selector(lockStatusChanged:) forControlEvents:UIControlEventValueChanged];
    
    self.speedPlayerSwitch = [[UISwitch alloc] init];
    [self.speedPlayerSwitch addTarget:self action:@selector(speedPlayerChanged:) forControlEvents:UIControlEventValueChanged];
    
    [self.view addSubview:self.lockStatusSwitch];
    [self.view addSubview:self.speedPlayerSwitch];
}

- (void)lockStatusChanged:(UISwitch *)sender {
    setLockStatus(sender.isOn);
}

- (void)speedPlayerChanged:(UISwitch *)sender {
    setSpeedPlayer(sender.isOn);
}

@end
