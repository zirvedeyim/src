ARCHS = arm64 arm64e
DEBUG = 0
FINALPACKAGE = 1
FOR_RELEASE = 1
THEOS_PACKAGE_SCHEME = rootless

include $(THEOS)/makefiles/common.mk

TWEAK_NAME = M7MD

# KeySystem ve Tweak için gerekli temel framework'ler
M7MD_FRAMEWORKS = UIKit Foundation WebKit

# Derleyici ayarları
M7MD_CFLAGS = -w -fobjc-arc -Wno-deprecated-declarations

# SADECE BU İKİ DOSYA DERLENECEK (Test aşaması)
M7MD_FILES = Tweak.mm KeySystem.mm

include $(THEOS_MAKE_PATH)/tweak.mk
