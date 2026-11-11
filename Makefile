ARCHS = arm64 arm64e
TARGET = iphone:clang:latest:14.0
THEOS_PACKAGE_SCHEME = rootless

include $(THEOS)/makefiles/common.mk

TWEAK_NAME = M7MD
M7MD_FRAMEWORKS = UIKit Foundation
M7MD_FILES = Tweak.mm

include $(THEOS_MAKE_PATH)/tweak.mk
