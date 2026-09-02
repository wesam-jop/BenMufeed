import AppLogoIcon from '@/components/app-logo-icon';
import BusinessBrand from '@/components/domain/BusinessBrand';

export default function AppLogo() {
    return (
        <BusinessBrand
            iconWrapperClassName="bg-sidebar-primary text-sidebar-primary-foreground"
            textClassName="text-left"
            fallbackIcon={
                <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
            }
        />
    );
}
